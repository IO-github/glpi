<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace tests\units;

use DbTestCase;
use Glpi\Socket;
use Glpi\SocketModel;

/* Test for inc/networkport.class.php */

class Cable extends DbTestCase
{
    public function testAddSocket()
    {

        $socket = getItemByTypeName(Socket::class, '_socket01');
        $location = getItemByTypeName('Location', '_location01');
        $expected = $socket->getName() . " (" . $location->getName() . ")";
        $ret = \Dropdown::getDropdownName('glpi_sockets', $socket->getID());
        $this->string($ret)->isIdenticalTo($expected);

       // test of return with comments
        $expected = ['name'    => $expected,
                  'comment' => "Comment for socket _socket01"];
        $ret = \Dropdown::getDropdownName('glpi_sockets', $socket->getID(), true);
        $this->array($ret)->isIdenticalTo($expected);

       // test of return without $tooltip
        $ret = \Dropdown::getDropdownName('glpi_sockets', $socket->getID(), true, true, false);
        $this->array($ret)->isIdenticalTo($expected);
    }

    public function testAddNetworkPortThenSocket()
    {
        $this->login();

       //First step add networkport
        $computer1 = getItemByTypeName('Computer', '_test_pc01');
        $networkport = new \NetworkPort();

       // Be sure added
        $nb_log = (int)countElementsInTable('glpi_logs');
        $new_id = $networkport->add([
         'items_id'           => $computer1->getID(),
         'itemtype'           => 'Computer',
         'entities_id'        => $computer1->fields['entities_id'],
         'is_recursive'       => 0,
         'logical_number'     => 1,
         'mac'                => '00:24:81:eb:c6:d0',
         'instantiation_type' => 'NetworkPortEthernet',
         'name'               => 'eth1',
        ]);
        $this->integer((int)$new_id)->isGreaterThan(0);
        $this->integer((int)countElementsInTable('glpi_logs'))->isGreaterThan($nb_log);

       //Second step add socket
       //add socket model
        $socketModel = new SocketModel();
        $nb_log = (int)countElementsInTable('glpi_logs');
        $socketModel_id = $socketModel->add([
         'name' => 'socketModel1'
        ]);
        $this->integer((int)$socketModel_id)->isGreaterThan(0);
        $this->integer((int)countElementsInTable('glpi_logs'))->isGreaterThan($nb_log);

        $socket = new Socket();
        $socket_id = $socket->add([
         'name'               => 'socket1',
         'position'           => 10,
         'networkports_id'    => $new_id,
         'wiring_side'        => Socket::FRONT, //default is REAR
         'items_id'           => $computer1->getID(),
         'itemtype'           => 'Computer',
         'socketmodels_id'    => $socketModel_id,
         'locations_id'       => 0,
         'comment'            => 'comment',
        ]);
        $this->integer((int)$socket_id)->isGreaterThan(0);

       // check data in db
        $all_sockets = getAllDataFromTable('glpi_sockets', ['ORDER' => 'id']);
        $current_socket = end($all_sockets);
        unset($current_socket['id']);
        unset($current_socket['date_mod']);
        unset($current_socket['date_creation']);
        $expected = [
         'position'           => 10,
         'locations_id'       => 0,
         'name'               => 'socket1',
         'socketmodels_id'    => $socketModel_id,
         'wiring_side'        => Socket::FRONT, //default is REAR
         'itemtype'           => 'Computer',
         'items_id'           => $computer1->getID(),
         'networkports_id'    => $new_id,
         'comment'            => 'comment',
        ];

        $this->array($current_socket)->isIdenticalTo($expected);
    }


    public function testBackwardCompatibility()
    {

       //test when sockets_id is defined from NetworkPort instanciation (NetworkPortEthernet, NetworkPortFiberChannel)
       //before it was the NetworkPort instantiation that had the socket reference
       //now it's the socket that have the networkport reference

        $this->login();

       //Second step add socket
       //add socket model
        $socketModel = new SocketModel();
        $nb_log = (int)countElementsInTable('glpi_logs');
        $socketModel_id = $socketModel->add([
         'name' => 'socketModel1'
        ]);
        $this->integer((int)$socketModel_id)->isGreaterThan(0);
        $this->integer((int)countElementsInTable('glpi_logs'))->isGreaterThan($nb_log);

        $socket = new Socket();
        $nb_log = (int)countElementsInTable('glpi_logs');
        $socket_id = $socket->add([
         'name'               => 'socket1',
         'wiring_side'        => Socket::FRONT, //default is REAR
         'itemtype'           => '',
         'socketmodels_id'    => $socketModel_id,
         'locations_id'       => 0,
         'comment'            => 'comment',
        ]);
        $this->integer((int)$socket_id)->isGreaterThan(0);
        $this->integer((int)countElementsInTable('glpi_logs'))->isGreaterThan($nb_log);

       //Second step add networkport
       // Do some installations
        $computer1 = getItemByTypeName('Computer', '_test_pc01');
        $networkport = new \NetworkPort();

       // Be sure added
        $nb_log = (int)countElementsInTable('glpi_logs');
        $new_id = $networkport->add([
         'items_id'                    => $computer1->getID(),
         'itemtype'                    => 'Computer',
         'entities_id'                 => $computer1->fields['entities_id'],
         'is_recursive'                => 0,
         'logical_number'              => 3,
         'mac'                         => '00:24:81:eb:c6:d2',
         'instantiation_type'          => 'NetworkPortEthernet',
         'name'                        => 'em3',
         'comment'                     => 'Comment me!',
         'items_devicenetworkcards_id' => 0,
         'type'                        => 'T',
         'speed'                       => 1000,
         'speed_other_value'           => '',
         'NetworkName_name'            => 'test1',
         'NetworkName_comment'         => 'test1 comment',
         'NetworkName_fqdns_id'        => 0,
         'NetworkName__ipaddresses'    => ['-1' => '192.168.20.1'],
         '_create_children'            => true // automatically add instancation, networkname and ipadresses
        ]);
        $this->integer($new_id)->isGreaterThan(0);
        $this->integer((int)countElementsInTable('glpi_logs'))->isGreaterThan($nb_log);

       // retrieve NEtworkPortEthernet automatically created
        $all_netportethernets = getAllDataFromTable('glpi_networkportethernets', ['ORDER' => 'id']);
        $networkportethernet = end($all_netportethernets);
        $networkPortethernet_id = $networkportethernet['id'];
        unset($networkportethernet['date_mod']);
        unset($networkportethernet['date_creation']);

       //specify sockets_id and update it
        $data = $networkportethernet;
        $data['id'] = $networkPortethernet_id;
        $data['sockets_id'] = $socket_id;
        $networkPort_ethernet = new \NetworkPortEthernet();
        $this->boolean($networkPort_ethernet->update($data))->isTrue();

       //reload socket to check if link to networkports_id is ok (with itemtype and items_id)
        $this->boolean($socket->getFromDB($socket_id))->isTrue();
        $this->string($socket->fields['itemtype'])->isIdenticalTo('Computer');
        $this->integer($socket->fields['items_id'])->isIdenticalTo($computer1->getID());
        $this->integer($socket->fields['networkports_id'])->isIdenticalTo($new_id);
    }


    public function testAddCable()
    {
        $this->login();

       //First step add networkport / socket for computer '_test_pc01'
        $computer1 = getItemByTypeName('Computer', '_test_pc01');
        $networkport1 = new \NetworkPort();

       // Be sure added
        $nb_log = (int)countElementsInTable('glpi_logs');
        $new1_id = $networkport1->add([
         'items_id'           => $computer1->getID(),
         'itemtype'           => 'Computer',
         'entities_id'        => $computer1->fields['entities_id'],
         'is_recursive'       => 0,
         'logical_number'     => 1,
         'mac'                => '00:24:81:eb:c6:d0',
         'instantiation_type' => 'NetworkPortEthernet',
         'name'               => 'eth1',
        ]);
        $this->integer((int)$new1_id)->isGreaterThan(0);
        $this->integer((int)countElementsInTable('glpi_logs'))->isGreaterThan($nb_log);

       //add socket model
        $socketModel1 = new SocketModel();
        $nb_log = (int)countElementsInTable('glpi_logs');
        $socketModel1_id = $socketModel1->add([
         'name' => 'socketModel1'
        ]);
        $this->integer((int)$socketModel1_id)->isGreaterThan(0);
        $this->integer((int)countElementsInTable('glpi_logs'))->isGreaterThan($nb_log);

       //add socket
        $socket1 = new Socket();
        $socket1_id = $socket1->add([
         'name'               => 'socket1',
         'position'           => 10,
         'networkports_id'    => $new1_id,
         'wiring_side'        => Socket::FRONT, //default is REAR
         'items_id'           => $computer1->getID(),
         'itemtype'           => 'Computer',
         'socketmodels_id'    => $socketModel1_id,
         'locations_id'       => 0,
         'comment'            => 'comment',
        ]);
        $this->integer((int)$socket1_id)->isGreaterThan(0);

       // check data in db
        $all_sockets = getAllDataFromTable('glpi_sockets', ['ORDER' => 'id']);
        $current_socket = end($all_sockets);
        unset($current_socket['id']);
        unset($current_socket['date_mod']);
        unset($current_socket['date_creation']);

        $expected = [
         'position'           => 10,
         'locations_id'       => 0,
         'name'               => 'socket1',
         'socketmodels_id'    => $socketModel1_id,
         'wiring_side'        => Socket::FRONT, //default is REAR
         'itemtype'           => 'Computer',
         'items_id'           => $computer1->getID(),
         'networkports_id'    => $new1_id,
         'comment'            => 'comment',
        ];

        $this->array($current_socket)->isIdenticalTo($expected);

       //Second step add networkport / socket form switch '_test_pc02'
        $computer2 = getItemByTypeName('Computer', '_test_pc02');
        $networkport = new \NetworkPort();

       // Be sure added
        $nb_log = (int)countElementsInTable('glpi_logs');
        $new2_id = $networkport->add([
         'items_id'           => $computer2->getID(),
         'itemtype'           => 'Computer',
         'entities_id'        => $computer2->fields['entities_id'],
         'is_recursive'       => 0,
         'logical_number'     => 1,
         'mac'                => '00:24:81:eb:c6:d0',
         'instantiation_type' => 'NetworkPortEthernet',
         'name'               => 'eth1',
        ]);
        $this->integer((int)$new2_id)->isGreaterThan(0);
        $this->integer((int)countElementsInTable('glpi_logs'))->isGreaterThan($nb_log);

       //add socket model
        $socketModel2 = new SocketModel();
        $nb_log = (int)countElementsInTable('glpi_logs');
        $socketModel2_id = $socketModel2->add([
         'name' => 'socketModel2'
        ]);
        $this->integer((int)$socketModel2_id)->isGreaterThan(0);
        $this->integer((int)countElementsInTable('glpi_logs'))->isGreaterThan($nb_log);

       //add socket
        $socket2 = new Socket();
        $socket2_id = $socket2->add([
         'name'               => 'socket2',
         'position'           => 10,
         'networkports_id'    => $new2_id,
         'wiring_side'        => Socket::FRONT, //default is REAR
         'items_id'           => $computer2->getID(),
         'itemtype'           => 'Computer',
         'socketmodels_id'    => $socketModel2_id,
         'locations_id'       => 0,
         'comment'            => 'comment',
        ]);
        $this->integer((int)$socket2_id)->isGreaterThan(0);

       // check data in db
        $all_sockets = getAllDataFromTable('glpi_sockets', ['ORDER' => 'id']);
        $current_socket = end($all_sockets);
        unset($current_socket['id']);
        unset($current_socket['date_mod']);
        unset($current_socket['date_creation']);
        $expected = [
         'position'           => 10,
         'locations_id'       => 0,
         'name'               => 'socket2',
         'socketmodels_id'    => $socketModel2_id,
         'wiring_side'        => Socket::FRONT, //default is REAR
         'itemtype'           => 'Computer',
         'items_id'           => $computer2->getID(),
         'networkports_id'    => $new2_id,
         'comment'            => 'comment',
        ];

        $this->array($current_socket)->isIdenticalTo($expected);

       //add CableStradn
        $cableStrand = new \CableStrand();
        $nb_log = (int)countElementsInTable('glpi_logs');
        $cableStrand_id = $cableStrand->add([
         'name' => 'cable_strand'
        ]);
        $this->integer((int)$cableStrand_id)->isGreaterThan(0);
        $this->integer((int)countElementsInTable('glpi_logs'))->isGreaterThan($nb_log);

       //add State
        $cableState = new \State();
        $nb_log = (int)countElementsInTable('glpi_logs');
        $cableState_id = $cableState->add([
         'name' => 'cable_state',
         'is_visible_cable' => true,
        ]);
        $this->integer((int)$cableState_id)->isGreaterThan(0);
        $this->integer((int)countElementsInTable('glpi_logs'))->isGreaterThan($nb_log);

       //add Cabletype
        $cableType = new \CableType();
        $nb_log = (int)countElementsInTable('glpi_logs');
        $cableType_id = $cableType->add([
         'name' => 'cable_type'
        ]);
        $this->integer((int)$cableType_id)->isGreaterThan(0);
        $this->integer((int)countElementsInTable('glpi_logs'))->isGreaterThan($nb_log);

       //add cable
        $cable = new \Cable();
        $cable_id = $cable->add([
         'name'                  => 'cable',
         'entities_id'           => $computer1->fields['entities_id'],
         'is_recursive'          => 0,
         'itemtype_endpoint_a'         => 'Computer',
         'itemtype_endpoint_b'        => 'Computer',
         'items_id_endpoint_a'         => $computer1->getID(),
         'items_id_endpoint_b'        => $computer2->getID(),
         'socketmodels_id_endpoint_a'  => $socketModel1_id,
         'socketmodels_id_endpoint_b' => $socketModel2_id,
         'sockets_id_endpoint_a'       => $socket1_id,
         'sockets_id_endpoint_b'      => $socket2_id,
         'cablestrands_id'       => $cableStrand_id,
         'color'                 => '#f72f04',
         'otherserial'           => 'otherserial',
         'sates_id'              => $cableState_id,
         'users_id_tech'         => 2,
         'cabletypes_id'         => $cableType_id,
         'comment'               => 'comment',
        ]);
        $this->integer((int)$cable_id)->isGreaterThan(0);

       // check data in db
        $all_cables = getAllDataFromTable('glpi_cables', ['ORDER' => 'id']);
        $current_cable = end($all_cables);
        unset($current_cable['id']);
        unset($current_cable['date_mod']);
        unset($current_cable['date_creation']);
        $expected = [
         'name'                  => 'cable',
         'entities_id'           => $computer1->fields['entities_id'],
         'is_recursive'          => 0,
         'itemtype_endpoint_a'         => 'Computer',
         'itemtype_endpoint_b'        => 'Computer',
         'items_id_endpoint_a'         => $computer1->getID(),
         'items_id_endpoint_b'        => $computer2->getID(),
         'socketmodels_id_endpoint_a'  => $socketModel1_id,
         'socketmodels_id_endpoint_b' => $socketModel2_id,
         'sockets_id_endpoint_a'       => $socket1_id,
         'sockets_id_endpoint_b'      => $socket2_id,
         'cablestrands_id'       => $cableStrand_id,
         'color'                 => '#f72f04',
         'otherserial'           => 'otherserial',
         'sates_id'              => $cableState_id,
         'users_id_tech'         => 2,
         'cabletypes_id'         => $cableType_id,
         'comment'               => 'comment',
        ];
    }
}
