<?php

namespace Blume\LaravelDTO\Attributes;

use Attribute;
use InvalidArgumentException;
use Blume\LaravelDTO\Interfaces\Castable;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Cast implements Castable
{
    /**
     * @param  class-string<Castable>  $cast  The primary cast class to instantiate.
     * @param  class-string<Castable>|mixed  $parameter  Optional: if this is itself a Castable class-string,
     *                                                   it is instantiated and passed as the inner cast.
     *                                                   Otherwise it is forwarded as a plain constructor argument.
     * @param  mixed  $secondParameter  Forwarded to the inner Castable when constructing a nested cast.
     */
    public function __construct(
        protected string $cast,
        protected mixed $parameter = null,
        protected mixed $secondParameter = null,
    ) {}

    public function cast(string $property, mixed $value): mixed
    {
        if (! class_exists($this->cast)) {
            throw new InvalidArgumentException('Cast class does not exist');
        }

        if (
            $this->parameter
            &&
            in_array(
                Castable::class,
                class_implements($this->parameter)
            )
        ) {
            if ($this->secondParameter) {
                $innerCast = new ($this->parameter)($this->secondParameter);
            } else {
                $innerCast = new ($this->parameter)();
            }

            $cast = new ($this->cast)($innerCast);
        } else {
            $cast = new ($this->cast)($this->parameter);
        }

        return $cast->cast($property, $value);
    }
}
