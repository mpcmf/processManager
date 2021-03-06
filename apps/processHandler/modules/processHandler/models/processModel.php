<?php
namespace mpcmf\modules\processHandler\models;

use mpcmf\modules\moduleBase\models\modelBase;
use mpcmf\system\pattern\singleton;

/**
 * Class processModel
 *
 * Process manager
 *
 *
 * @generated by mpcmf/codeManager
 *
 * @package mpcmf\modules\processHandler\models
 * @date 2017-01-20 16:14:08
 *
 * @author Gregory Ostrovsky <greevex@gmail.com>
 * @author Gadel Raymanov <raymanovg@gmail.com>
 *
 * @method string getMongoId() Mongo ID
 * @method $this setMongoId(string $value) Mongo ID
 * @method string getDescription() Description
 * @method $this setDescription(string $value) Description
 * @method int getUpdatedAt() Updated at
 * @method $this setUpdatedAt(int $value) Updated at
 * @method string getName() Name
 * @method $this setName(string $value) Name
 * @method string getState() State
 * @method $this setState($value) State
 * @method string getMode() Mode
 * @method $this setMode(string $value) Mode
 * @method int getPeriod() Period time
 * @method $this setPeriod(int $value) Period time
 * @method string getServer() Mode
 * @method $this setServer(string $value) Mode
 * @method string getCommand() Command
 * @method $this setCommand(string $value) Command
 * @method string setWorkDir(string $value) Work dir
 * @method $this getWorkDir() Work dir
 * @method int getInstances() Instances
 * @method $this setInstances(int $value) Instances
 * @method int getForksCount() ForksCount
 * @method $this setForksCount(int $value) ForksCount
 * @method array getTags() Tags
 * @method $this setTags(array $value) Tags
 * @method array getLogging() Params of logging
 * @method $this setLogging(array $value) Params of logging
 * @method $this setHost(string $value) Server's address where will be started process
 * @method string getHost() Server's address where will be started process
 */
class processModel
    extends modelBase
{

    use singleton;
}