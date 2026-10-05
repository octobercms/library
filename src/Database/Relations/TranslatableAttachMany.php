<?php namespace October\Rain\Database\Relations;

/**
 * TranslatableAttachMany is an attachMany relation that stores its files per locale.
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class TranslatableAttachMany extends AttachMany
{
    use TranslatableAttachOneOrMany;
}
