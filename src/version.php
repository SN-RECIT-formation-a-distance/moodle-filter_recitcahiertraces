<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @copyright 2019 RÉCIT 
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
$plugin->version   = 2020022901;        // The current plugin version (Date: YYYYMMDDXX)
$plugin->requires  = 2018050800;        // Requires this Moodle version
$plugin->component = 'filter_recitcahiercanada'; // Full name of the plugin (used for diagnostics)
// This is a list of plugins, this plugin depends on (and their versions).                                                          
$plugin->dependencies = [                                                                                                           
    'local_recitcommon' => '2020022900', // ANY_VERSION
	'mod_recitcahiercanada' => '2020022900'
];
$plugin->release = 'R5-2020022902'; 
$plugin->maturity = MATURITY_BETA; // MATURITY_ALPHA, MATURITY_BETA, MATURITY_RC or MATURITY_STABLE