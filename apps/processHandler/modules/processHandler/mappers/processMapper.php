<?php
namespace mpcmf\modules\processHandler\mappers;

use mpcmf\apps\processHandler\libraries\processManager\processHandler;
use mpcmf\modules\moduleBase\mappers\mapperBase;
use mpcmf\system\pattern\singleton;

/**
 * Class processMapper
 *
 * Process manager
 *
 *
 * @generated by mpcmf/codeManager
 *
 * @package mpcmf\modules\processHandler\mappers
 * @date 2017-01-20 16:14:08
 *
 * @author Gregory Ostrovsky <greevex@gmail.com>
 * @author Gadel Raymanov <raymanovg@gmail.com>
 */
class processMapper
    extends mapperBase
{

    use singleton;

    const FIELD___ID = '_id';
    const FIELD__NAME = 'name';
    const FIELD__DESCRIPTION = 'description';
    const FIELD__STATE = 'state';
    const FIELD__MODE = 'mode';
    const FIELD__COMMAND = 'command';
    const FIELD__INSTANCES = 'instances';
    const FIELD__WORK_DIR = 'work_dir';
    const FIELD__SERVER = 'server';
    const FIELD__TAGS = 'tags';
    const FIELD__LOGGING = 'logging';
    const FIELD__STD_OUT = 'std_out';
    const FIELD__STD_ERROR = 'std_error';
    const FIELD__FORKS_COUNT = 'forks_count';
    const FIELD__UPDATE_AT = 'updated_at';
    const FIELD__CREATED_AT = 'created_at';

    const MODE__ONE_RUN = 'one_run';
    const MODE__REPEATABLE = 'repeatable';
    const MODE__PERIODIC = 'periodic';
    const MODE__TIMER = 'timer';
    const MODE__CRON = 'cron';

    public function getPublicName()
    {
        return 'Processes';
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
                    'key' => true,
                    'generate-key' => true,
                ],
                'name' => 'Mongo ID',
                'description' => 'Mongo ID',
                'type' => 'string',
                'formType' => 'text',
                'validator' => [],
                'relations' => [],
                'options' => [
                    'required' => false,
                    'unique' => true,
                ],
            ],
            self::FIELD__NAME => [
                'getter' => 'getName',
                'setter' => 'setName',
                'role' => [
                    'title' => true,
                    'searchable' => true,
                    'sortable' => true,
                ],
                'name' => 'Name',
                'description' => 'Name',
                'type' => 'string',
                'formType' => 'text',
                'validator' => [
                    [
                        'type' => 'string.byLength',
                        'data' => [
                            'length' => [
                                'min' => 2,
                                'max' => PHP_INT_MAX
                            ]
                        ]
                    ]
                ],
                'relations' => [],
                'options' => [
                    'required' => true,
                    'unique' => true,
                ],
            ],
            self::FIELD__DESCRIPTION => [
                'getter' => 'getDescription',
                'setter' => 'setDescription',
                'role' => [],
                'name' => 'Description',
                'description' => 'Description',
                'type' => 'string',
                'formType' => 'text',
                'validator' => [],
                'relations' => [],
                'options' => [
                    'required' => false,
                    'unique' => false,
                ],
            ],
            self::FIELD__STATE => [
                'getter' => 'getState',
                'setter' => 'setState',
                'role' => [
                    self::ROLE__SEARCHABLE => true,
                    'sortable' => true,
                ],
                'name' => 'State',
                'description' => 'State',
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
                            'pattern' => '/^(' . processHandler::STATE__NEW . '|'
                                . processHandler::STATE__RUN . '|'
                                . processHandler::STATE__RUNNING . '|'
                                . processHandler::STATE__STOP . '|'
                                . processHandler::STATE__STOPPING .'|'
                                . processHandler::STATE__STOPPED .'|'
                                . processHandler::STATE__RESTART .'|'
                                . processHandler::STATE__RESTARTING .'|'
                                . processHandler::STATE__REMOVE .'|'
                                . processHandler::STATE__READY_TO_REMOVE_FROM_DB .'|'
                                . processHandler::STATE__REMOVING .')$/'
                        ]
                    ]
                ],
                'relations' => [],
                'options' => [
                    'required' => true,
                    'unique' => false,
                ],
            ],
            self::FIELD__MODE => [
                'getter' => 'getMode',
                'setter' => 'setMode',
                'role' => [],
                'name' => 'Mode',
                'description' => 'Mode',
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
                                . self::MODE__REPEATABLE . '|'
                                . self::MODE__ONE_RUN . '|'
                                . self::MODE__PERIODIC . '|'
                                . self::MODE__TIMER . '|'
                                . self::MODE__CRON .')$/'
                        ]
                    ]
                ],
                'relations' => [],
                'options' => [
                    'required' => true,
                    'unique' => false,
                ],
            ],
            self::FIELD__COMMAND => [
                'getter' => 'getCommand',
                'setter' => 'setCommand',
                'role' => [],
                'name' => 'Command',
                'description' => 'Command',
                'type' => 'string',
                'formType' => 'text',
                'validator' => [
                    [
                        'type' => 'string.byLength',
                        'data' => [
                            'length' => [
                                'min' => 2,
                                'max' => PHP_INT_MAX

                            ]
                        ]
                    ]
                ],
                'relations' => [],
                'options' => [
                    'required' => true,
                    'unique' => false,
                ],
            ],
            self::FIELD__WORK_DIR => [
                'getter' => 'getWorkDir',
                'setter' => 'setWorkDir',
                'role' => [],
                'name' => 'Work dir',
                'description' => 'Work dir',
                'type' => 'string',
                'formType' => 'text',
                'validator' => [
                    [
                        'type' => 'string.byLength',
                        'data' => [
                            'length' => [
                                'min' => 1,
                                'max' => PHP_INT_MAX
                            ]
                        ]
                    ]
                ],
                'relations' => [],
                'options' => [
                    'required' => true,
                    'unique' => false,
                ],
            ],
            self::FIELD__TAGS => [
                'getter' => 'getTags',
                'setter' => 'setTags',
                'role' => [
                    self::ROLE__FULLTEXT_SEARCH => true,
                ],
                'name' => 'Tags',
                'description' => 'Tags',
                'type' => 'string[]',
                'formType' => 'multitext',
                'validator' => [],
                'relations' => [],
                'options' => [
                    'required' => false,
                    'unique' => false,
                ],
            ],
            self::FIELD__LOGGING => [
                'getter' => 'getLogging',
                'setter' => 'setLogging',
                'role' => [
                ],
                'name' => 'Logging',
                'description' => 'Params of logging',
                'type' => 'array',
                'formType' => 'json',
                'validator' => [],
                'relations' => [],
                'options' => [
                    'required' => false,
                    'unique' => false,
                ],
            ],
            self::FIELD__INSTANCES => [
                'getter' => 'getInstances',
                'setter' => 'setInstances',
                'role' => [],
                'name' => 'Instances',
                'description' => 'Instances',
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
                'relations' => [],
                'options' => [
                    'required' => true,
                    'unique' => false,
                ],
            ],
            self::FIELD__FORKS_COUNT => [
                'getter' => 'getForksCount',
                'setter' => 'setForksCount',
                'role' => [],
                'name' => 'ForksCount',
                'description' => 'ForksCount',
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
                'relations' => [],
                'options' => [
                    'required' => false,
                    'unique' => false,
                ],
            ],
            self::FIELD__SERVER => [
                'getter' => 'getServer',
                'setter' => 'setServer',
                'role' => [
                    self::ROLE__SEARCHABLE => true
                ],
                'name' => 'Host Name',
                'description' => 'Server\'s address where will be started process',
                'type' => 'string',
                'formType' => 'select',
                'validator' => [
                    [
                        'type' => 'string.byLength',
                        'data' => [
                            'length' => [
                                'min' => 24,
                                'max' => 24
                            ]
                        ]
                    ]
                ],
                'relations' => [
                    'creator' => [
                        'getter' => 'getServerModel',
                        'setter' => 'setServerModel',
                        'type' => self::RELATION__ONE_TO_ONE,
                        'mapper' => serverMapper::class,
                    ]
                ],
                'options' => [
                    'required' => true,
                    'unique' => false,
                ],
            ],
            self::FIELD__UPDATE_AT => [
                'getter' => 'getUpdatedAt',
                'setter' => 'setUpdatedAt',
                'role' => [
                    self::ROLE__SORTABLE => true
                ],
                'name' => 'Updated at',
                'description' => 'Updated at',
                'type' => 'int',
                'formType' => 'datetimepicker',
                'validator' => [],
                'relations' => [],
                'options' => [
                    'required' => false,
                    'unique' => false,
                ],
            ],
            self::FIELD__CREATED_AT => [
                'getter' => 'getCreatedAt',
                'setter' => 'setCreatedAt',
                'role' => [
                    self::ROLE__SORTABLE => true
                ],
                'name' => 'Created at',
                'description' => 'Created at',
                'type' => 'int',
                'formType' => 'datetimepicker',
                'validator' => [],
                'relations' => [],
                'options' => [
                    'required' => false,
                    'unique' => false,
                ],
            ]
        ];
    }
}