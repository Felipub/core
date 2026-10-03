<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Module\QueryBuilder\Domain\QueryGateway;

$_POST['address'] = '/modules/Query Builder/queries.php';

// Gibbon system-wide includes
include '../../gibbon.php';

// Setup variables
$gibboneduComOrganisationName = $_GET['gibboneduComOrganisationName'];
$gibboneduComOrganisationKey = $_GET['gibboneduComOrganisationKey'];
$service = $_GET['service'];

$queryGateway = $container->get(QueryGateway::class);

// Remove all gibbonedu.com queries
$queryGateway->deleteWhere(['type' => 'gibbonedu.com']);
