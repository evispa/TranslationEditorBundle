<?php
/**
 * @author Nerijus Arlauskas
 */

namespace Nercury\TranslationEditorBundle\Form;

/**
 * Abstracts the property path differences between symfony 2.1 and 2.3.
 */
class PropertyAccessor
{
    private $propertyAccessor;
    private $propertyPaths = array();

    public function __construct()
    {
        if (class_exists('Symfony\Component\PropertyAccess\PropertyAccess')) {
            $this->propertyAccessor = \Symfony\Component\PropertyAccess\PropertyAccess::createPropertyAccessor();
        } else {
            $this->propertyAccessor = false;
        }
    }

    /**
     * Get property path object.
     *
     * @param string $propertyPath Property path string.
     *
     * @return \Symfony\Component\Form\Util\PropertyPath
     */
    private function getPropertyPath($propertyPath)
    {
        if (!isset($this->propertyPaths[$propertyPath])) {
            $this->propertyPaths[$propertyPath] = new \Symfony\Component\Form\Util\PropertyPath($propertyPath);
        }
        return $this->propertyPaths[$propertyPath];
    }

    public function getValue($object, $propertyPath)
    {
        if (false === $this->propertyAccessor) {
            return $this->getPropertyPath($propertyPath)->getValue($object);
        } else {
            return $this->propertyAccessor->getValue($object, $propertyPath);
        }
    }

    public function setValue($object, $propertyPath, $value)
    {
        if (false === $this->propertyAccessor) {
            return $this->getPropertyPath($propertyPath)->setValue($object, $value);
        } else {
            return $this->propertyAccessor->setValue($object, $propertyPath, $value);
        }
    }
}
