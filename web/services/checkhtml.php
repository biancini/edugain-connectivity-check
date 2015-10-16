<?php
# Copyright 2015 GÃ©ant Association
#
# Licensed under the GÃ‰ANT Standard Open Source (the "License")
# you may not use this file except in compliance with the License.
# 
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
#
# This software was developed by Consortium GARR. The research leading to
# these results has received funding from the European CommunityÂ¹s Seventh
# Framework Programme (FP7/2007-2013) under grant agreement nÂº 238875
# (GÃ‰ANT).

require_once 'CheckHtml.php';

$handler = new CheckHtml();
try {
    print $handler->handle();
}
catch (Exception $e) {
    print $e->getMessage();
}
