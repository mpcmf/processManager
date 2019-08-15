<?php

namespace mpcmf\modules\processHandler\mappers;

use mpcmf\modules\moduleBase\mappers\mapperBase;
use mpcmf\system\configuration\config;
use mpcmf\system\pattern\singleton;

/**
 * @author Gadel Raymanov <raymanovg@gmail.com>
 */
class statsMapper
    extends mapperBase
{

    use singleton;

    const FIELD___ID = '_id';
    const FIELD__PROCESS_COMMAND = 'process_command';
    const FIELD__PROCESS_MODE = 'process_mode';
    const FIELD__PROCESS_INSTANCES = 'process_instances';
    const FIELD__ACTION_TYPE = 'action_type';
    const FIELD__ACTION_AT = 'action_at';
    const FIELD__SERVER = 'server';

    const ACTION_TYPE__START = 'start';
    const ACTION_TYPE__STOP = 'stop';

    public function __construct()
    {
        parent::__construct();
        $this->checkIndexes();
    }

    public function getPublicName()
    {
        return 'Process stats';
    }

    /**
     * Entity map
     *
     * @return array[]
     */
    public function getMap()
    {
        return [
            self::FIELD___ID => [
                'getter' => 'getMongoId',
                'setter' => 'setMongoId',
                'role' => [
                    self::ROLE__PRIMARY_KEY => true,
                    self::ROLE__GENERATE_KEY => true,
                ],
                'name' => 'Mongo ID',
                'description' => 'Mongo ID',
                'type' => 'string',
                'formType' => 'text',
                'validator' => [],
                'options' => [
                    'required' => false,
                    'unique' => true,
                ],
            ],
            self::FIELD__PROCESS_COMMAND => [
                'getter' => 'getProcessCommand',
                'setter' => 'setProcessCommand',
                'role' => [
                    self::ROLE__FULLTEXT_SEARCH => true,
                ],
                'name' => 'Process command',
                'description' => 'Process command',
                'type' => 'string',
                'formType' => 'text',
                'validator' => [
                    [
                        'type' => 'type.check',
                        'data' => [
                            'type' => 'string'
                        ]
                    ]
                ],
                'options' => [
                    'required' => true,
                    'unique' => false,
                ],
            ],
            self::FIELD__PROCESS_MODE => [
                'getter' => 'getProcessMode',
                'setter' => 'setProcessMode',
                'role' => [
                    self::ROLE__SORTABLE => true,
                ],
                'name' => 'Process mode',
                'description' => 'Process mode',
                'type' => 'string',
                'formType' => 'text',
                'validator' => [
                    [
                        'type' => 'type.check',
                        'data' => [
                            'type' => 'string'
                        ]
                    ],
                    [
                        'type' => 'string.byRegex',
                        'data' => [
                            'pattern' => '/^('
                                . processMapper::MODE__REPEATABLE . '|'
                                . processMapper::MODE__ONE_RUN . '|'
                                . processMapper::MODE__PERIODIC . '|'
                                . processMapper::MODE__TIMER . '|'
                                . processMapper::MODE__CRON .')$/'
                        ]
                    ]
                ],
                'options' => [
                    'required' => true,
                    'unique' => false,
                ],
            ],
            self::FIELD__PROCESS_INSTANCES => [
                'getter' => 'getProcessInstances',
                'setter' => 'setProcessInstances',
                'role' => [
                    self::ROLE__SORTABLE => true
                ],
                'name' => 'Process instances',
                'description' => 'Process instances count',
                'type' => 'int',
                'formType' => 'text',
                'validator' => [
                    [
                        'type' => 'type.check',
                        'data' => [
                            'type' => 'int'
                        ]
                    ]
                ],
                'options' => [
                    'required' => true,
                    'unique' => false,
                ],
            ],
            self::FIELD__ACTION_TYPE => [
                'getter' => 'getActionType',
                'setter' => 'setActionType',
                'role' => [
                    self::ROLE__SORTABLE => true,
                ],
                'name' => 'Action type',
                'description' => 'Action type',
                'type' => 'string',
                'formType' => 'text',
                'validator' => [
                    [
                        'type' => 'type.check',
                        'data' => [
                            'type' => 'string'
                        ]
                    ],
                    [
                        'type' => 'string.byRegex',
                        'data' => [
                            'pattern' => '/^(' . self::ACTION_TYPE__START . '|' . self::ACTION_TYPE__STOP . ')$/'
                        ]
                    ]
                ],
                'options' => [
                    'required' => true,
                    'unique' => false,
                ],
            ],
            self::FIELD__ACTION_AT => [
                'getter' => 'getActionAt',
                'setter' => 'setActionAt',
                'role' => [
                    self::ROLE__SORTABLE => true
                ],
                'name' => 'Action at',
                'description' => 'Action time',
                'type' => 'int',
                'formType' => 'datetimepicker',
                'validator' => [
                    [
                        'type' => 'type.check',
                        'data' => [
                            'type' => 'int'
                        ]
                    ]
                ],
                'options' => [
                    'required' => true,
                    'unique' => false,
                ],
            ],
            self::FIELD__SERVER => [
                'getter' => 'getServer',
                'setter' => 'setServer',
                'role' => [
                    self::ROLE__TITLE => true,
                    self::ROLE__SEARCHABLE => true,
                    self::ROLE__SORTABLE => true,
                ],
                'name' => 'Server',
                'description' => 'Server address',
                'type' => 'string',
                'formType' => 'text',
                'validator' => [
                    [
                        'type' => 'type.check',
                        'data' => [
                            'type' => 'string'
                        ]
                    ]
                ],
                'options' => [
                    'required' => true,
                    'unique' => false,
                ],
            ]
        ];
    }

    public function checkIndexes()
    {
        $storageConfig = config::getConfig(__CLASS__)['storage'];

        $this->storage()->checkIndexes($storageConfig['db'], $storageConfig['collection'], $storageConfig['indices']);
    }}