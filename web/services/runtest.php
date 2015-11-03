<?php
# Copyright 2015 Géant Association
#
# Licensed under the GEANT Standard Open Source (the "License")
# you may not use this file except in compliance with the License.
# 
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
#
# This software was developed by Consortium GARR. The research leading to
# these results has received funding from the European Community's Seventh
# Framework Programme (FP7/2007-2013) under grant agreement nº 238875
# (GEANT)

require_once 'RunTest.php';

$handler = new RunTest;
header("Location: " . $handler->handle());