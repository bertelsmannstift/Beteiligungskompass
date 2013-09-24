<?php

/** @MappedSuperclass */
abstract class Model_Base {
	public function __get($property) {
		if(!property_exists($this, $property)) {
			return false;
		}

		$getter = 'get' . ucfirst($property);
		if(method_exists($this, $getter)) return $this->$getter();

		return $this->$property;
	}

	public function __set($property, $value) {
		if(!property_exists($this, $property)) {
			return false;
		}

		$setter = 'set' . ucfirst($property);
		if(method_exists($this, $setter)) return $this->$setter($value);

		$this->$property = $value;
		return true;
	}

	public function metaData() {
		$cmf = Doctrine::instance()->getMetadataFactory();
		return $class = $cmf->getMetadataFor(get_class($this));
	}

	public function fieldMapping() {
		return $this->metaData()->fieldMappings;
	}

    public function stripTags($value) {
        $notAllow = array('script','iframe','embed', 'object');
        foreach($notAllow as $tag) {
            $value = preg_replace('#<' . $tag . '(.*?)>(.*?)</' . $tag . '>#is', '', $value);
        }
        return $value;
    }

	public function from_array(array $values) {
		foreach($values as $property => $value) {

			if(is_string($value)) {
				$value = $this->stripTags($value);
			}

            if($property === 'date') {
                $this->__set($property, DateTime::createFromFormat('d.m.Y', $value));
            } else {
                $this->__set($property, $value);
            }

		}
	}

	public function to_array() {
		$values = array();
		foreach($this->fieldMapping() as $field) {
			$values[$field['fieldName']] = $this->__get($field['fieldName']);
		}

		return $values;
	}
}