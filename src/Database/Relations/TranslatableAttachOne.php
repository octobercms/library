<?php namespace October\Rain\Database\Relations;

/**
 * TranslatableAttachOne is an attachOne relation that stores its file per locale.
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class TranslatableAttachOne extends AttachOne
{
    use TranslatableAttachOneOrMany;
}
