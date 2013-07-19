What it is
==========

It is a translation editor form.

![Translation editor example](https://github.com/Nercury/translation-editor-bundle/blob/master/Resources/public/images/translations-preview.jpg?raw=true)

Using it with array
===================

Example data:

```php
$data = array(
    'translations' => array(
        array(
            'lang' => 'lt',
            'name' => 'Produktas',
            'description' => 'Produkto aprašymas'
        ),
        array(
            'lang' => 'en',
            'name' => 'Product',
            'description' => 'Product description',
        ),
    ),
);
```

Form requires that you create a form type for inner item, in this case it is named "TestType":

```php
class TestType extends \Symfony\Component\Form\AbstractType
{
    public function buildForm(\Symfony\Component\Form\FormBuilderInterface $builder, array $options)
    {
        $builder->add('name');
        $builder->add('description');
    }

    public function getName()
    {
        return 'translation_item_form';
    }
}
```

Form type is implemented in similar fassion as a collection. Use "type" to specify inner type form. Use "locale_field_name" to override locale field name (default is "lang").

```php
$formBuilder = $this->createFormBuilder($data);
$formBuilder->add('translations', 'translations', array(
    'type' => new TestType(),
    'locale_field_name' => '[lang]',
    'locales' => array('lt', 'en', 'ru'),
));
```

Using it with a collection and entities
=======================================

Form type also works with database translations defined in this or smilar table structure:

```

| product    |        | product_translation |
| id         | ------ | product_id          |
                      | lang                |
                      | name (localised)    |
```

It follows the same style the old Doctrine1 behaviours used to do it.

Main Product entity must have a doctrine collection of translations, and "getTranslations()" method, and you should build your form using its name.

It will find other stuff in Doctrine class metadata.

Form theme
==========

You can override form theme by overriding "fields.html.twig" file.

Javascript
==========

```
@NercuryTranslationEditorBundle/Resources/public/js/translations_form.js
```
