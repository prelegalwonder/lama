<?php

class ProtocolController extends mdb_Controller_Simple {

    const ACL_RESOURCE = 'default_protocol';

    protected $_table = 'protocols';
    protected $_modelClass = 'Protocols';
    protected $_formClass = 'forms_Protocol';
    protected $_item = 'Protocol';
    protected $_controller_path = 'protocol';
    protected $_assigned_id_col = 'protocol_name';

}
