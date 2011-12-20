<?php

namespace Kappa;

use kyResultSet;

class ResultSet extends kyResultSet
{
    public function key()
    {
        return $this->current()->getId();
    }

    public function offsetExists($offset)
    {
        foreach ($this->getRawArray() as $object) {
            if ($object->getId() == $offset) {
                return true;
            }
        }

        return false;
    }

    public function offsetGet($offset)
    {
        foreach ($this->getRawArray() as $object) {
            if ($object->getId() == $offset) {
                return $object;
            }
        }

        return null;
    }

    public function offsetSet($offset, $value)
    {
        foreach ($this->getRawArray() as $key => $object) {
            if ($object->getId() == $offset) {
                parent::offsetSet($offset, $value);
            }
        }

        // todo remove hack
        for ($i = 0; ; $i++) {
            if (!parent::offsetExists($i)) {
                parent::offsetSet($i, $value);
            }
        }
    }

    public function offsetUnset($offset)
    {
        foreach ($this->getRawArray() as $key => $object) {
            if ($object->getId() == $offset) {
                parent::offsetUnset($key);
            }
        }
    }
}
