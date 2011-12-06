TypehintableBehavior
====================

The **TypehintableBehavior** behavior allows you to add typehints to generated methods (in _Base_ classes).


Installation
------------

Cherry-pick the `TypehintableBehavior.php` file is `src/`, put it somewhere,
then add the following line to your `propel.ini` or `build.properties` configuration file:

``` ini
propel.behavior.typehintable.class = path.to.TypehintableBehavior
```


Usage
-----

Just add the following XML tag in your `schema.xml` file:

``` xml
<behavior name="typehintable">
    <parameter name="COLUMN_NAME" value="TYPEHINT" />
</behavior>
```


Example
-------

``` xml
<table name="user">
    <column name="id" type="integer" required="true" primaryKey="true" autoIncrement="true" />
    <column name="username" type="varchar" size="255" primaryString="true" />
    <column name="roles" type="array" />

    <behavior name="typehintable">
        <parameter name="roles" value="array" />
    </behavior>
</table>
```

It will generate the following code:

``` php
<?php

public function setRoles(array $roles)
{
}
```


Credits
-------

William Durand <william.durand1@gmail.com>
