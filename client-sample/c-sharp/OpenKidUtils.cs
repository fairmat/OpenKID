///
// This file contains utility classes to access easily OpenKID repositories
// in order to extract information from them
//
// LICENCE:
// Copyright (c) 2016 Fairmat SRL.
// Permission is hereby granted, free of charge, to any person obtaining
// a copy of this software and associated documentation files
// (the "Software"), to deal in the Software without restriction,
// including without limitation the rights to use, copy, modify, merge,
// publish, distribute, sublicense, and/or sell copies of the Software,
// and to permit persons to whom the Software is furnished to do so,
// subject to the following conditions:
// 
// The above copyright notice and this permission notice shall be included
// in all copies or substantial portions of the Software.
// 
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
// EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
// OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
// IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
// CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
// TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
// SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
//
// <copyright>Copyright (c) 2016 Fairmat SRL (http://www.fairmat.com)</copyright>
// <license>MIT.</license>
// <version>0.1</version>
///
///
// An implementation of several utils to access OpenKID repositories.
//
// <copyright>Copyright (c) 2016 Fairmat SRL (http://www.fairmat.com)</copyright>
// <license>MIT.</license>
// <version>0.1</version>
///

using openKid.Model;
using System;
using System.Collections.Generic;
using System.Globalization;
using System.Linq;
using System.Net.Http;
using System.Threading.Tasks;
using System.Xml;
using System.Xml.Linq;

namespace openKid.Util
{
    public static class OpenKidUtils
    {
        /// <summary>
        /// Inner function that loads an XML file from an url.
        /// </summary>
        /// <param name="xmlUri"> 
        /// The position of the xml file to open.
        /// </param>
        /// <returns>
        /// A parsed XML file object.
        /// </returns>
        private static async Task<XDocument> LoadXML(Uri xmlUri)
        {
            using (var client = new HttpClient())
            {
                XDocument xmlDocument = null;

                client.BaseAddress = xmlUri;
                client.DefaultRequestHeaders.Accept.Clear();
                var response = await client.GetAsync(xmlUri);

                if (response.IsSuccessStatusCode)
                {
                    var responseStream = await response.Content.ReadAsStringAsync();

                    xmlDocument = XDocumentTryParse(responseStream);
                }

                return xmlDocument;
            }
        }

        private static XDocument XDocumentTryParse(string xmlFile)
        {
            try
            {
                return XDocument.Parse(xmlFile);
            }
            catch (XmlException)
            {
                return null;
            }
        }

        /// <summary>
        /// Gets the last time the repository was updated (by checking the last updated field of the index file)
        /// </summary>
        /// <param name="repoUrl"> 
        /// The URL of the repository to check for KIDs (check repo-example for an idea of its structure).
        /// </param>
        /// <returns>
        /// A DateTime object with the last update date in it.
        /// </returns>
        public static async Task<DateTime?> GetLastUpdate(string repoUrl)
        {
            var xmlDoc = await LoadXML(CombineUri(repoUrl, "kidsindex.xml"));

            return ConvertToDate(xmlDoc.Root.Element("LastUpdated")?.Value);
        }

        /// <summary>
        ///  Gets a list of all KIDs in the index of the provided repository url.
        /// </summary>
        /// <param name="repoUrl"> 
        /// The URL of the repository to check for KIDs (check repo-example for an idea of its structure).
        /// </param>
        /// <returns>
        /// A list of all KIDs entry.
        /// </returns>
        public static async Task<IList<KidEntry>> GetAllAvailableKIDs(string repoUrl)
        {
            IList<KidEntry> kidEntryList = new List<KidEntry>();

            var xmlDoc = await LoadXML(CombineUri(repoUrl, "kidsindex.xml"));

            if (xmlDoc != null)
            {
                var entries = from e in xmlDoc.Root.Elements("KIDS").Elements("KIDEntry")
                              select e;

                foreach (var entry in entries)
                {
                    kidEntryList.Add(ParseSingleKIDEntry(entry));
                }

            }

            return kidEntryList;
        }

        /// <summary>
        /// Inner function which extracts a single KID entry from the index file.
        /// </summary>
        /// <param name="entry"> 
        /// A XML element pointing related to a <KIDEntry>
        /// </param>
        /// <returns>
        /// A KID entry
        /// </returns>
        private static KidEntry ParseSingleKIDEntry(XElement entry)
        {
            var kidEntry = new KidEntry()
            {
                Issuer = entry.Element("Issuer")?.Value,
                Isin = entry.Element("ISIN")?.Value,
                ProductName = entry.Element("ProductName")?.Value,
                FirstPublished = ConvertToDate(entry.Element("FirstPublished")?.Value),
                LastUpdated = ConvertToDate(entry.Element("LastUpdated")?.Value),
                HistoryUrl = entry.Element("History")?.Value,
            };

            kidEntry.Versions = ParseVersionsEntry(entry);

            return kidEntry;
        }

        /// <summary>
        /// Inner function used to parse <Versions>...</Versions> data as the format is shared between the history and main index files.
        /// </summary>
        /// <param name="entry"> 
        /// A XML element pointing related to a <KIDEntry>
        /// </param>
        /// <returns>
        /// A list of all versions
        /// </returns>
        private static IList<KidVersion> ParseVersionsEntry(XElement entry)
        {
            var kidVersionList = new List<KidVersion>();

            var versions = from v in entry.Elements("Versions").Elements("Version")
                           select v;

            foreach (var version in versions)
            {
                var kidVersion = new KidVersion()
                {
                    Language = version.Element("Language").Value,
                    Url = version.Element("Url").Value
                };

                kidVersionList.Add(kidVersion);
            }

            return kidVersionList;
        }

        /// <summary>
        /// Gets a list of all KIDs in the index of the provided repository url, which have been updated or added since the provided date.
        /// </summary>
        /// <param name="repoUrl"> 
        /// The URL of the repository to check for KIDs (check repo-example for an idea of its structure).
        /// </param>
        /// <param name="minDate"> 
        /// The date to check against to filter out older KIDs.
        /// </param>
        /// <returns>
        /// A list of all KIDs entry updated or added after the specified date
        /// </returns>
        public static async Task<IList<KidEntry>> GetAllAvailableKIDsAfterDate(string repoUrl, DateTime minDate)
        {
            var kidList = await GetAllAvailableKIDs(repoUrl);

            var filteredKidList = from f in kidList
                                  where (f.FirstPublished ?? DateTime.MinValue) >= minDate || (f.LastUpdated ?? DateTime.MinValue) >= minDate
                                  select f;

            return filteredKidList.ToList();
        }

        /// <summary>
        /// Gets the publishing history of a specific ISIN from the history file associated to it.
        /// This is done by first checking for the index file to contain the ISIN of the product we are interested into, then checking the
        /// reference within the history folder of the repository. That file is then parsed to provide the full history of the products we are interested into.
        /// </summary>
        /// <param name="repoUrl"> 
        /// The URL of the repository to check for KIDs (check repo-example for an idea of its structure).
        /// </param>
        /// <param name="isin"> 
        /// The product ISIN we are interested into.
        /// </param>
        /// <returns>
        /// A list of history entries provided by the correlated history file.
        /// </returns>
        public static async Task<IList<KidUpdate>> GetProductPublishingHistory(string repoUrl, string isin)
        {
            IList<KidUpdate> kidUpdateList = new List<KidUpdate>();

            var xmlDoc = await LoadXML(CombineUri(repoUrl, "kidsindex.xml"));

            if (xmlDoc != null)
            {
                var entry = (
                                from e in xmlDoc.Root.Elements("KIDS").Elements("KIDEntry")
                                where isin.Equals(e.Element("ISIN").Value)
                                select e
                            ).FirstOrDefault();

                if (entry != null)
                {
                    var historyUrl = entry.Element("History")?.Value;

                    if (historyUrl != null)
                    {
                        var xmlHistoryDoc = await LoadXML(CombineUri(repoUrl, historyUrl));

                        var updateEntries = from e in xmlHistoryDoc.Root.Elements("Update")
                                            select e;

                        foreach (var updateEntry in updateEntries)
                        {
                            var kidUpdate = new KidUpdate()
                            {
                                PublishDate = ConvertToDate(updateEntry.Element("PublishDate")?.Value),
                            };

                            kidUpdate.Versions = ParseVersionsEntry(updateEntry);

                            kidUpdateList.Add(kidUpdate);
                        }
                    }
                }
            }

            return kidUpdateList;
        }

        private static Uri CombineUri(string baseUri, string relativeOrAbsoluteUri)
        {
            return new Uri(new Uri(baseUri), relativeOrAbsoluteUri);
        }

        private static DateTime? ConvertToDate(string dateString)
        {
            if (string.IsNullOrEmpty(dateString))
            {
                return null;
            }

            return DateTime.ParseExact(dateString, "yyyy-MM-dd", CultureInfo.InvariantCulture);
        }

    }
}
