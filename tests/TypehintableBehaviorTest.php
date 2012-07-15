<?php

/*
 * This file is part of the TypehintableBehavior package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

/**
 * Tests for TypehintableBehaviorTest class
 *
 * @author William Durand <william.durand1@gmail.com>
 */
class TypehintableBehaviorTest extends \PHPUnit_Framework_TestCase
{
    private $schema;

    public function setUp()
    {
        $this->schema = <<<EOF
<database name="bookstore" defaultIdMethod="native">
    <table name="typehinted_object">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <column name="name" type="VARCHAR" size="100" primaryString="true" />
        <column name="roles" type="array" />

        <behavior name="typehintable">
            <parameter name="roles" value="array" />
        </behavior>
    </table>

    <table name="typehinted_user">
        <column name="id" type="INTEGER" primaryKey="true" autoIncrement="true"/>
        <column name="name" type="VARCHAR" size="32"/>
        <column name="catched_exception" type="OBJECT" />
        <column name="foo" type="OBJECT" />

        <behavior name="typehintable">
            <parameter name="typehinted_group" value="BaseTypehintedGroup" />
            <parameter name="catched_exception" value="Exception" />
            <parameter name="foo" value="TypehintedUser" />

            <parameter name="nullable_columns" value="foo, catched_exception" />
        </behavior>
    </table>

    <table name="typehinted_group">
        <column name="id" type="INTEGER" primaryKey="true" autoIncrement="true"/>
        <column name="name" type="VARCHAR" size="32"/>
    </table>

    <table name="typehinted_user_group" isCrossRef="true">
        <column name="user_id" type="INTEGER" primaryKey="true"/>
        <column name="group_id" type="INTEGER" primaryKey="true"/>

        <foreign-key foreignTable="typehinted_user">
            <reference local="user_id" foreign="id"/>
        </foreign-key>
        <foreign-key foreignTable="typehinted_group">
            <reference local="group_id" foreign="id"/>
        </foreign-key>
    </table>
</database>
EOF;

        if (!class_exists('TypehintedObject')) {
            $builder = new PropelQuickBuilder();
            $config  = $builder->getConfig();
            $config->setBuildProperty('behavior.typehintable.class', __DIR__.'/../src/TypehintableBehavior');
            $builder->setConfig($config);
            $builder->setSchema($this->schema);
            $builder->build();
        }
    }

    public function testTypehintIsAdded()
    {
        $o = new TypehintedObject();
        $o->setRoles(array());
        $this->assertTrue(true);
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testErrorReportedIfStringGiven()
    {
        $o = new TypehintedObject();
        $o->setRoles('toto');
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testErrorReportedIfNullGiven()
    {
        $o = new TypehintedObject();
        $o->setRoles(null);
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testErrorReportedIfNoneGiven()
    {
        $o = new TypehintedObject();
        $o->setRoles();
    }

    public function testTypehintOnManyToManyRelation()
    {
        $u = new TypehintedUser();
        $g = new TypehintedGroup();

        $u->addTypehintedGroup($g);
        $this->assertTrue(true);
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testTypehintOnManyToManyRelationFailsIfTypeMismatches()
    {
        $u = new TypehintedUser();
        $g = new TypehintedObject();

        $u->addTypehintedGroup($g);
    }

    public function testTypehintOnManyToManyRelationRemover()
    {
        $ref = new ReflectionClass('TypehintedUser');
        $parameters = $ref->getMethod('removeTypehintedGroup')->getParameters();

        $this->assertEquals('BaseTypehintedGroup', $parameters[0]->getClass()->getName());
    }

    public function testTypehintIsNullable()
    {
        $u = new TypehintedUser();
        $u->setCatchedException(null);
        $this->assertTrue(true);
    }

    public function testTypehintIsNullableAndEarlyClassUsage()
    {
        $u = new TypehintedUser();
        $u->setFoo(null);
        $this->assertTrue(true);
    }

    public function testSchemaIsValid()
    {
        $xml = new DOMDocument();
        $xml->loadXML($this->schema);

        $this->assertTrue($xml->schemaValidate(__DIR__ . '/../vendor/propel/propel1/generator/resources/xsd/database.xsd'));
    }
}
