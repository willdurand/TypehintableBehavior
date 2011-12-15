<?php

/*
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

$propeldir = dirname(__FILE__) . '/../../../propel';
require_once $propeldir . '/test/tools/helpers/bookstore/BookstoreTestBase.php';
require_once $propeldir . '/generator/lib/util/PropelQuickBuilder.php';
require_once $propeldir . '/runtime/lib/Propel.php';

require_once __DIR__ . '/../src/TypehintableBehavior.php';

/**
 * Tests for TypehintableBehaviorTest class
 *
 * @author     William Durand <william.durand1@gmail.com>
 * @package    generator.behavior
 */
class TypehintableBehaviorTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!class_exists('TypehintedObject')) {
            $schema = <<<EOF
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

        <behavior name="typehintable">
            <parameter name="group" value="\TypehintedGroup" />
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
            $builder = new PropelQuickBuilder();
            $config = $builder->getConfig();
            $config->setBuildProperty('behavior.typehintable.class', __DIR__.'/../src/TypehintableBehavior');
            $builder->setConfig($config);
            $builder->setSchema($schema);
            $con = $builder->build();
        }
    }

    public function testTypehintIsAdded()
    {
        $o = new TypehintedObject();
        $o->setRoles(array());
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
}
