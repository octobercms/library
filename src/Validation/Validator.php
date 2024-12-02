<?php namespace October\Rain\Validation;

use Site;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\Validator as ValidatorBase;
use October\Rain\Exception\ValidationException;

/**
 * Validator is a modifier to the base class, it extends validation rules
 * with extra methods for specifying messages and replacements inside the
 * class definition.
 */
class Validator extends ValidatorBase
{
    use \October\Rain\Validation\Concerns\FormatsMessages;

    /**
     * @var string exception to throw upon failure.
     */
    protected $exception = ValidationException::class;

    /**
     * validateUniqueSite validates the uniqueness of an attribute value on a given database table,
     * including a site ID condition check.
     */
    public function validateUniqueSite($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'unique_site');

        [$connection, $table, $idColumn] = $this->parseTable($parameters[0]);

        // The second parameter position holds the name of the column that needs to
        // be verified as unique. If this parameter isn't specified we will just
        // assume that this column to be verified shares the attribute's name.
        $column = $this->getQueryColumn($parameters, $attribute);

        $id = null;

        if (isset($parameters[2])) {
            [$idColumn, $id] = $this->getUniqueIds($idColumn, $parameters);

            if (!is_null($id)) {
                $id = stripslashes($id);
            }
        }

        // The presence verifier is responsible for counting rows within this store
        // mechanism which might be a relational database or any other permanent
        // data store like Redis, etc. We will use it to determine uniqueness.
        $verifier = $this->getPresenceVerifier($connection);

        $extra = $this->getUniqueExtra($parameters);

        if ($this->currentRule instanceof Unique) {
            $extra = array_merge($extra, $this->currentRule->queryCallbacks());
        }

        // Add the site extra
        $extra['site_id'] = Site::getSiteIdFromContext();

        return $verifier->getCount(
            $table, $column, $value, $id, $idColumn, $extra
        ) == 0;
    }
}
