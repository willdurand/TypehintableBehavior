TypehintableBehavior
====================

[![Build Status](https://secure.travis-ci.org/willdurand/TypehintableBehavior.png)](http://travis-ci.org/willdurand/TypehintableBehavior)

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
    <parameter name="RELATED_TABLE_NAME" value="TYPEHINT" />
</behavior>
```

If you fill in a column name as parameter's name, the typehint will be added to the corresponding setter methods (`setRoles()` for instance).
If you fill in a related table name as parameter's name, the typehint will be added to the adder/remover methods (`addGroup()`, `removeGroup()` for instance).

You can also specify nullable method signatures (`setFoo(Foo $foo = null)`):

``` xml
<parameter name="nullable_columns" value="COLUMN_NAME, RELATED_TABLE_NAME" />
```


Example
-------

``` xml
<table name="user">
    <column name="id" type="integer" required="true" primaryKey="true" autoIncrement="true" />
    <column name="username" type="varchar" size="255" primaryString="true" />
    <column name="roles" type="array" />

    <behavior name="typehintable">
		<!-- A column -->
        <parameter name="roles" value="array" />
        <!-- A related table -->
		<parameter name="group" value="\FOS\UserBundle\Model\GroupInterface" />
    </behavior>
</table>

<table name="group">
	<column name="id" type="integer" required="true" primaryKey="true" autoIncrement="true" />
	<column name="name" type="varchar" size="255" required="true" primaryString="true" />
</table>

<table name="user_group" isCrossRef="true">
	<column name="user_id" type="integer" required="true" primaryKey="true" />
	<column name="group_id" type="integer" required="true" primaryKey="true" />

	<foreign-key foreignTable="user">
		<reference local="user_id" foreign="id" />
	</foreign-key>

	<foreign-key foreignTable="group">
		<reference local="group_id" foreign="id" />
	</foreign-key>
</table>
```

It will generate the following code in the `BaseUser` class:

``` php
<?php

use FOS\UserBundle\Model\GroupInterface;

// ...

public function setRoles(array $v)
{
    if ($this->roles_unserialized !== $v) {
        $this->roles_unserialized = $v;
        $this->roles = '| ' . implode(' | ', $v) . ' |';
        $this->modifiedColumns[] = UserPeer::ROLES;
    }

    return $this;
}

public function addGroup(GroupInterface $group)
{
    if ($this->collGroups === null) {
        $this->initGroups();
    }
    if (!$this->collGroups->contains($group)) { // only add it if the **same** object is not already associated
        $this->doAddGroup($group);

        $this->collGroups[]= $group;
    }
}
```


Credits
-------

William Durand <william.durand1@gmail.com>
