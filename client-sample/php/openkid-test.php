<?php

/**
* This file contains some sample usage of functions provided by the
* OpenKIDUtils class.
*
* LICENCE:
* Copyright (c) 2016 Fairmat SRL.
* Permission is hereby granted, free of charge, to any person obtaining
* a copy of this software and associated documentation files
* (the "Software"), to deal in the Software without restriction,
* including without limitation the rights to use, copy, modify, merge,
* publish, distribute, sublicense, and/or sell copies of the Software,
* and to permit persons to whom the Software is furnished to do so,
* subject to the following conditions:
* 
* The above copyright notice and this permission notice shall be included
* in all copies or substantial portions of the Software.
* 
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
* EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
* OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
* IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
* CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
* TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
* SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*
* @copyright  Copyright (c) 2016 Fairmat SRL (http://www.fairmat.com)
* @license    MIT.
* @version    0.1
*/

include "openkid-util.php";

// Edit depending on your repository position
$repoURL = "C:\\Users\\stefano\\Desktop\\OpenKID\\OpenKID\\repo-sample\\";
$sampleISIN = "IT0123456789";
$minDate = DateTime::createFromFormat('Y-m-d', "2017-02-01");

// Call all available functions
print_r(OpenKIDUtils::getLastUpdate($repoURL));
print_r(OpenKIDUtils::getAllAvailableKIDs($repoURL));
print_r(OpenKIDUtils::getAllAvailableKIDsAfterDate($repoURL, $minDate));
print_r(OpenKIDUtils::getProductPublishingHistory($repoURL, $sampleISIN));

?>
