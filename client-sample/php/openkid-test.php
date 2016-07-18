<?php

/**
* This file contains some sample usage of functions provided by the
* OpenKIDUtils class.
*
* LICENSE:
* Copyright (c) 2016, Fairmat
* All rights reserved.
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions are met:
*
* 1. Redistributions of source code must retain the above copyright notice,
*    this list of conditions and the following disclaimer.
*
* 2. Redistributions in binary form must reproduce the above copyright notice,
*    this list of conditions and the following disclaimer in the documentation
*    and/or other materials provided with the distribution.
*
* THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
* AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
* IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
* ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS
* BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
* CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
* SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
* INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER
* IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
* ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED
* OF THE POSSIBILITY OF SUCH DAMAGE.
*
* @copyright  Copyright (c) 2016 Fairmat SRL (http://www.fairmat.com)
* @license    BSD License 2-Clause.
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
