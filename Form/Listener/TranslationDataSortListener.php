<?php
/**
 * @author nerijus
 */

namespace Nercury\TranslationEditorBundle\Form\Listener;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

/**
 * Sorts translation locales in correct order, appends missing locale entities.
 */
class TranslationDataSortListener implements EventSubscriberInterface
{
    /**
     * @var string
     */
    private $localePropertyPath;

    /**
     * @var string
     */
    private $itemDataClass;

    private $nullLocaleEnabled;

    /**
     * @var array
     */
    private $locales;

    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * Array of fields which are always considered empty no matter the contents (for translation removal).
     *
     * @var array
     */
    private $autoRemoveIgnoreFields;

    /**
     * Create new data sort listener.
     *
     * @param string $localePropertyPath Locale field property path.
     * @param string $itemDataClass Translation item data class.
     * @param array $locales Locale array for editing.
     * @param boolean $nullLocaleEnabled Is a special null locale enabled? If so, it will be prepended at the start.
     * @param ObjectManager $objectManager Doctrine object manager, optional.
     * @param array $autoRemoveIgnoreFields Array of fields which are always considered empty
     *                                      no matter the contents (for translation removal).
     */
    public function __construct(
        $localePropertyPath,
        $itemDataClass,
        $locales,
        $nullLocaleEnabled = false,
        $objectManager = null,
        $autoRemoveIgnoreFields = array()
    ) {
        $this->localePropertyPath = $localePropertyPath;
        $this->itemDataClass = $itemDataClass;
        $this->nullLocaleEnabled = $nullLocaleEnabled;
        $this->locales = $locales;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->objectManager = $objectManager;
        $this->autoRemoveIgnoreFields = $autoRemoveIgnoreFields;
    }

    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::POST_SUBMIT => 'postSubmit',
        );
    }

    private function getParentDataObject($form) {
        $parentForm = $form->getParent();
        if ($parentForm === null) {
            return null;
        }
        $parentData = $parentForm->getData();
        if (is_object($parentData)) {
            return $parentData;
        }
        return null;
    }

    /**
     * @param string $className
     * @return ClassMetadata
     */
    private function getClassMetadata($className)
    {
        if (null === $this->objectManager) {
            return null;
        }
        return $this->objectManager->getClassMetadata($className);
    }

    private function getParentMappingFieldName($parentDataClass, $itemDataClass)
    {
        if (null === $parentDataClass || null === $itemDataClass) {
            return null;
        }

        // If parent and child are both objects, we will get the doctrine association
        // metadata, so that we could assign the parent to a new child correctly.

        $itemMetadata = $this->getClassMetadata($itemDataClass);

        if (null === $itemMetadata) {
            return null;
        }

        $associations = $itemMetadata->getAssociationMappings();
        foreach ($associations as $name => $options) {
            if ($parentDataClass === $options['targetEntity'] && $itemDataClass === $options['sourceEntity']) {
                return $options['fieldName'];
            }
        }

        return null;
    }

    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        if ($data instanceof Collection) {
            $dataInArray = false;
        } else {
            $dataInArray = true;
        }

        if ($dataInArray && !is_array($data)) {
            $data = array();
        }

        $existingData = $dataInArray ? $data : $data->toArray();
        $existingItemsByLang = array();

        // Find existing translations.

        foreach ($existingData as $dataItem) {
            $locale = $this->propertyAccessor->getValue($dataItem, $this->localePropertyPath);
            if (null === $locale) {
                $existingItemsByLang['__'] = $dataItem;
            } else {
                $existingItemsByLang[$locale] = $dataItem;
            }
        }

        // Create locale list. Allow special "null" locale in it in case it is enabled.

        $iterateLocales = $this->locales;

        if ($this->nullLocaleEnabled) {
            array_splice($iterateLocales, 0, 0, array('__'));
        }

        // Different code in case the collection data is array or Collection.

        if (is_array($data)) {

            $data = array();

            // Iterate the locales in the same order they are supplied,
            // for each existing locale add it into list, for each new locale
            // create a new object or array.

            foreach ($iterateLocales as $locale) {
                if (isset($existingItemsByLang[$locale])) {
                    $data[] = $existingItemsByLang[$locale];
                } else {

                    // If locale object is missing, create a new one.
                    // We assume that we can create a new object based on item data_class option.

                    $newItem = array();

                    if ($this->itemDataClass !== null) {
                        $r = new \ReflectionClass($this->itemDataClass);
                        $newItem = $r->newInstanceArgs();
                    }

                    $this->propertyAccessor->setValue($newItem, $this->localePropertyPath, '__' === $locale ? null : $locale);
                    $data[] = $newItem;
                }
            }

            $event->setData($data);

        } else {

            $parentData = null;
            $parentPropertyPath = null;

            // If item is in collection, it may require a parent to be assigned.
            // For example, a product translation may require a assigned product entity.

            if (null !== $this->itemDataClass) {

                // If the item is not array (has data_class), we bother getting parent
                // object and its data class.

                $parentData = $this->getParentDataObject($form);

                $parentDataClass = null;
                if (null !== $parentData) {
                    $parentDataClass = get_class($parentData);
                }

                if (null !== $parentDataClass) {
                    $parentPropertyPath = $this->getParentMappingFieldName($parentDataClass, $this->itemDataClass);
                }

            }

            $data->clear();

            foreach ($iterateLocales as $locale) {
                if (isset($existingItemsByLang[$locale])) {
                    $data->add($existingItemsByLang[$locale]);
                } else {

                    // If locale object is missing, create a new one.
                    // We assume that we can create a new object based on item data_class option.

                    $newItem = array();

                    if ($this->itemDataClass !== null) {
                        $r = new \ReflectionClass($this->itemDataClass);
                        $newItem = $r->newInstanceArgs();
                        if ($parentPropertyPath !== null) {
                            $this->propertyAccessor->setValue($newItem, $parentPropertyPath, $parentData);
                        }
                    }

                    $this->propertyAccessor->setValue($newItem, $this->localePropertyPath, '__' === $locale ? null : $locale);
                    $data[] = $newItem;
                }
            }

            $event->setData($data);

        }
    }

    /**
     * This method bind after form submit to clean empty translation entities.
     *
     * @param \Symfony\Component\Form\FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $options = $event->getForm()->getConfig()->getOptions();

        if ((null !== $this->itemDataClass)  && (true == $options['auto_remove_empty_translations'])) {
            $itemMetadata = $this->getClassMetadata($this->itemDataClass);
            if (null !==  $itemMetadata) {
                $this->cleanupCollection($data, $itemMetadata);
            }
        }
    }

    /**
     * This method cleans empty translation entities.
     *
     * @param mixed $collection
     * @param ClassMetadata $classMetadata
     */
    private function cleanupCollection(&$collection, $classMetadata)
    {
        $notNullableStringProperties = array();
        $nullableStringProperties = array();
        $otherProperties = array();
        foreach ($classMetadata->getFieldNames() as $fieldName) {
            $fieldMapping = $classMetadata->getFieldMapping($fieldName);
            // skip id field
            if (isset($fieldMapping['id']) && $fieldMapping['id'] === true) {
                continue;
            }
            // skip language field
            if (in_array($fieldMapping['fieldName'], $this->autoRemoveIgnoreFields) || $fieldMapping['fieldName'] === $this->localePropertyPath) {
                continue;
            }

            if ($fieldMapping['nullable']) {
                if ($fieldMapping['type'] === 'string') {
                    $nullableStringProperties[] = new PropertyPath($fieldMapping['fieldName']);
                } else {
                    $otherProperties[] = new PropertyPath($fieldMapping['fieldName']);
                }
            } else {
                if ($fieldMapping['type'] === 'string') {
                    $notNullableStringProperties[] = new PropertyPath($fieldMapping['fieldName']);
                } else {
                    $otherProperties[] = new PropertyPath($fieldMapping['fieldName']);
                }
            }
        }

        // Clean null elements from collection.
        if (is_array($collection)) {
            foreach ($collection as $key => $item) {
                if (null === $item) {
                    unset($collection[$key]);
                }
            }
        } else {
            foreach ($collection as $item) {
                if (null === $item) {
                    $collection->removeElement($item);
                }
            }
        }

        $toRemove = array();

        foreach ($collection as $entity) { // entity is translation, i.e. $sitemapNodeTranslation
            $allNullableStringsAreNull = true;
            foreach ($nullableStringProperties as $pp) { // $pp is property path, i.e. 'short_description'
                // this calls something like $sitemapNodeTranslation->getShortDescription()
                $value = $this->propertyAccessor->getValue($entity, $pp);
                if (trim($value) === '') {
                    $this->propertyAccessor->setValue($entity, $pp, null);
                } else {
                    $allNullableStringsAreNull = false;
                }
            }

            $allNotNullableStringsAreNull = true;
            foreach ($notNullableStringProperties as $pp) {
                $value = $this->propertyAccessor->getValue($entity, $pp);
                if ($value !== null) {
                    if (trim($value) !== '') {
                        $allNotNullableStringsAreNull = false;
                    }
                } else {
                    $this->propertyAccessor->setValue($entity, $pp, '');
                }
            }

            $otherAreNull = true;
            foreach ($otherProperties as $pp) {
                $value = $this->propertyAccessor->getValue($entity, $pp);
                if ($value !== null) {
                    $otherAreNull = false;
                }
            }

            if ($allNullableStringsAreNull && $allNotNullableStringsAreNull && $otherAreNull) {
                $toRemove[] = $entity;
                if (null !== $this->objectManager && $this->objectManager->contains($entity)) {
                    $this->objectManager->remove($entity); // Delete from db.
                }
            }
        }

        $this->removeFromCollection($collection, $toRemove);
    }

    /**
     * Remove items from collection.
     *
     * @param Collection $collection Collection.
     * @param array $items Items to remove.
     */
    private function removeFromCollection(&$collection, $items)
    {
        if (is_array($collection)) {
            foreach ($items as $item) {
                foreach ($collection as $index => $v) {
                    if (spl_object_hash($item) == spl_object_hash($v)) {
                        unset($collection[$index]);
                    }
                }
            }
        } else {
            foreach ($items as $item) {
                $collection->removeElement($item);
            }
        }
    }
}
