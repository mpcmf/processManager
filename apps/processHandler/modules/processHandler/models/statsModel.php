<?php

namespace mpcmf\modules\processHandler\models;

use mpcmf\modules\moduleBase\models\modelBase;
use mpcmf\system\pattern\singleton;

/**
 *
 * @package mpcmf\modules\processHandler\models
 * @date 2019-08-14 13:00:00
 *
 * @author Gadel Raymanov <raymanovg@gmail.com>
 *
 * @method string getMongoId() MongoId
 * @method $this setMongoId(string $value) MongoId
 * @method string getProcessCommand() Name
 * @method $this setProcessCommand(string $value) Name
 * @method string getProcessMode() Name
 * @method $this setProcessMode(string $value) Name
 * @method int getProcessInstances() Instances count
 * @method $this setProcessInstances(int $value) Instances count
 * @method string getActionType() Action type
 * @method $this setActionType(string $value) Action type
 * @method int getActionAt() Action time
 * @method $this setActionAt $value) Action time
 * @method string getServer() Server host name
 * @method $this setServer(string $value) Server host name
 */
class statsModel
    extends modelBase
{

    use singleton;
}