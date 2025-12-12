<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UniqueIgnoringSoftDeletes implements ValidationRule
{
    protected string $modelClass;
    protected string $attribute;
    protected ?int $exceptId;

    /**
     * Create a new rule instance.
     *
     * @param string $modelClass The model class to check uniqueness against.
     * @param string $attribute The attribute to validate for uniqueness.
     * @param int|null $exceptId The ID to exclude from the uniqueness check (optional).
     */
    public function __construct(string $modelClass, string $attribute, ?int $exceptId = null)
    {
        $this->modelClass = $modelClass;
        $this->attribute = $attribute;
        $this->exceptId = $exceptId;
    }

    /**
     * Validate the given attribute against the uniqueness rule, ignoring soft-deleted records.
     *
     * @param string $attribute The name of the attribute being validated.
     * @param mixed $value The value of the attribute being validated.
     * @param \Closure $fail The closure to call if validation fails.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /** @var Model $model */
        $model = new $this->modelClass;

        $query = $this->modelClass::where($this->attribute, $value);

        // Handle soft deletes only if model uses it
        if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
            $query->whereNull($model->getDeletedAtColumn());
        }

        if ($this->exceptId !== null) {
            $query->where('id', '!=', $this->exceptId);
        }

        if ($query->exists()) {
            $fail(__('The :attribute has already been taken.'));
        }
    }
}
