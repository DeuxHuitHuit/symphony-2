<?php

namespace Symphony\XML\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers XMLElement
 */
final class XMLElementTest extends TestCase
{
    public function testDefaultValues()
    {
        $x = new \XMLElement('xml');
        $this->assertEquals('xml', $x->getName());
        $this->assertEmpty($x->getValue());
        $this->assertEquals(0, $x->getNumberOfChildren());
        $this->assertEmpty($x->getChildren());
        $this->assertEmpty($x->getAttributes());
        $this->assertEquals('xml', $x->getElementStyle());
        $this->assertEquals('<xml />', $x->generate());
    }

    public function testValueInConstructor()
    {
        $x = new \XMLElement('xml', 'value');
        $this->assertEquals('xml', $x->getName());
        $this->assertEquals('value', $x->getValue());
        $this->assertEquals(1, $x->getNumberOfChildren());
        $this->assertNotEmpty($x->getChildren());
        $this->assertEmpty($x->getAttributes());
        $this->assertEquals('<xml>value</xml>', $x->generate());
    }

    public function testAttributesAndValueInConstructor()
    {
        $x = new \XMLElement('xml', 'value', ['attr' => 'yes', 'null' => null]);
        $this->assertEquals('xml', $x->getName());
        $this->assertEquals('value', $x->getValue());
        $this->assertEquals(1, $x->getNumberOfChildren());
        $this->assertNotEmpty($x->getChildren());
        $this->assertNotEmpty($x->getAttributes());
        $this->assertEquals('yes', $x->getAttribute('attr'));
        $this->assertEquals('<xml attr="yes" null="">value</xml>', $x->generate());
    }

    public function testAttributesAndValueInConstructorWithHandles()
    {
        $x = new \XMLElement('x m l', 'value', ['attr' => 'yes', 'null' => null], true);
        $this->assertEquals('x-m-l', $x->getName());
        $this->assertEquals('value', $x->getValue());
        $this->assertEquals(1, $x->getNumberOfChildren());
        $this->assertNotEmpty($x->getChildren());
        $this->assertNotEmpty($x->getAttributes());
        $this->assertEquals('yes', $x->getAttribute('attr'));
        $this->assertEquals('<x-m-l attr="yes" null="">value</x-m-l>', $x->generate());
    }

    public function testEmptyAttributes()
    {
        $x = (new \XMLElement('xml'))
            ->setAttribute('null', null)
            ->setAttributeArray(['not-empty' => '1', 'empty' => ''])
            ->setAllowEmptyAttributes(false);
        $this->assertNotEmpty($x->getAttributes());
        $this->assertEquals('<xml not-empty="1" />', $x->generate());
        $x->renderEmptyAttributes();
        $this->assertEquals('<xml null="" not-empty="1" empty="" />', $x->generate());
    }

    public function testAddClass()
    {
        $x = (new \XMLElement('xml'))->addClass('test');
        $this->assertEquals('<xml class="test" />', $x->generate());
        $x->addClass('test');
        $this->assertEquals('<xml class="test test" />', $x->generate());
        $x->addClass('test2');
        $this->assertEquals('<xml class="test test test2" />', $x->generate());
    }

    public function testGenerateWithSelfClosing()
    {
        $x = (new \XMLElement('xml', 'value'));
        $this->assertEquals('<xml>value</xml>', $x->generate());
        $x->renderSelfClosingTag();
        $this->assertEquals('<xml>value</xml>', $x->generate());
        $x = (new \XMLElement('xml', null));
        $this->assertEquals('<xml />', $x->generate());
        $x->setSelfClosingTag(false);
        $this->assertEquals('<xml></xml>', $x->generate());
    }

    public function testGenerateForceNoEndTag()
    {
        $x = (new \XMLElement('br', null));
        $this->assertEquals('<br />', $x->generate());
        $x->setElementStyle('html');
        $this->assertEquals('<br>', $x->generate());
    }

    public function testGetChildrenByName()
    {
        $x = (new \XMLElement('xml'))
            ->appendChild((new \XMLElement('child'))->setValue('1'))
            ->appendChild((new \XMLElement('child-not'))->setValue('2'))
            ->appendChild((new \XMLElement('child'))->setValue('3'));
        $this->assertNotEmpty($x->getChildren());
        $this->assertEquals(3, $x->getNumberOfChildren());
        $this->assertEquals('3', $x->getChildByName('child', 1)->getValue());
    }

    public function testGetChildrenByNameWithValue()
    {
        $x = (new \XMLElement('xml', 'value'))
            ->appendChild((new \XMLElement('child'))->setValue('1'))
            ->appendChild((new \XMLElement('child-not'))->setValue('2'))
            ->appendChild((new \XMLElement('child'))->setValue('3'));
        $this->assertNotEmpty($x->getChildren());
        $this->assertEquals(4, $x->getNumberOfChildren());
        $this->assertEquals('3', $x->getChildByName('child', 1)->getValue());
    }

    public function testGetValue()
    {
        $x = new \XMLElement('xml', 'value');
        $this->assertEquals('value', $x->getValue());
        $x = new \XMLElement('xml', ['value', 'value2']);
        $this->assertEquals('value, value2', $x->getValue());
        $x = new \XMLElement('xml', new \XMLElement('value', 'value'));
        $this->assertEquals('<value>value</value>', $x->getValue());
        // TODO: make this work
        //$x = new \XMLElement('xml', [new \XMLElement('value', 'value')]);
        //$this->assertEquals('<value>value</value>', $x->getValue());
    }

    public function testSetValue()
    {
        $x = (new \XMLElement('xml'))->setValue('value');
        $this->assertEquals('value', $x->getValue());
    }

    public function testSetAttribute()
    {
        $x = (new \XMLElement('xml'))->setAttribute('value', 'yes');
        $this->assertEquals('yes', $x->getAttribute('value'));
        $this->assertNull($x->getAttribute('undefined'));
    }

    public function testWithChildrenFormatted()
    {
        $nl = PHP_EOL;
        $x = (new \XMLElement('xml'))
            ->appendChild((new \XMLElement('child'))->setValue('x'))
            ->appendChild((new \XMLElement('child'))->setValue('y'));
        $this->assertEquals("<xml>$nl\t<child>x</child>$nl\t<child>y</child>$nl</xml>$nl", $x->generate(true));
    }

    /**
     * @expectedException Exception
     */
    public function testInvalidSetChildren()
    {
        $x = (new \XMLElement('xml'));
        $x->setChildren([$x]);
    }

    public function testAppend()
    {
        $x = (new \XMLElement('xml'))
            ->appendChild((new \XMLElement('child'))->setValue('1'))
            ->appendChild((new \XMLElement('child'))->setValue('2'))
            ->appendChild((new \XMLElement('child'))->setValue('3'));
        $this->assertNotEmpty($x->getChildren());
        $this->assertEquals(3, $x->getNumberOfChildren());
        $this->assertEquals('<xml><child>1</child><child>2</child><child>3</child></xml>', $x->generate());
    }

    /**
     * @expectedException Exception
     */
    public function testInvalidAppend()
    {
        $x = (new \XMLElement('xml'));
        $x->appendChild($x);
    }

    public function testAppendArray()
    {
        $x = (new \XMLElement('xml'))
            ->appendChildArray([
                (new \XMLElement('child'))->setValue('1'),
                (new \XMLElement('child'))->setValue('2'),
                (new \XMLElement('child'))->setValue('3'),
            ]);
        $this->assertNotEmpty($x->getChildren());
        $this->assertEquals(3, $x->getNumberOfChildren());
        $this->assertEquals('<xml><child>1</child><child>2</child><child>3</child></xml>', $x->generate());
    }

    /**
     * @expectedException Exception
     */
    public function testInvalidAppendArray()
    {
        $x = (new \XMLElement('xml'));
        $x->appendChildArray([$x]);
    }

    public function testPrepend()
    {
        $x = (new \XMLElement('xml'))
            ->prependChild((new \XMLElement('child'))->setValue('3'))
            ->prependChild((new \XMLElement('child'))->setValue('2'))
            ->prependChild((new \XMLElement('child'))->setValue('1'));
        $this->assertNotEmpty($x->getChildren());
        $this->assertEquals(3, $x->getNumberOfChildren());
        $this->assertEquals('<xml><child>1</child><child>2</child><child>3</child></xml>', $x->generate());
    }

    /**
     * @expectedException Exception
     */
    public function testInvalidPrepend()
    {
        $x = (new \XMLElement('xml'));
        $x->prependChild($x);
    }

    public function testRemoveAt()
    {
        $x = (new \XMLElement('xml'))
            ->appendChild((new \XMLElement('child'))->setValue('1'))
            ->appendChild((new \XMLElement('child'))->setValue('2'))
            ->appendChild((new \XMLElement('child'))->setValue('3'))
            ->removeChildAt(1);
        $this->assertNotEmpty($x->getChildren());
        $this->assertEquals(2, $x->getNumberOfChildren());
        $this->assertEquals('<xml><child>1</child><child>3</child></xml>', $x->generate());
    }

    /**
     * @expectedException Exception
     */
    public function testInvalidRemoveAt()
    {
        $x = (new \XMLElement('xml'));
        $x->removeChildAt(0);
    }

    /**
     * @expectedException Exception
     */
    public function testInvalidRemoveAtUnsetIndex()
    {
        $x = (new \XMLElement('xml'))
            ->appendChild((new \XMLElement('child'))->setValue('1'))
            ->appendChild((new \XMLElement('child'))->setValue('2'))
            ->appendChild((new \XMLElement('child'))->setValue('3'))
            ->removeChildAt(1);
        $x->removeChildAt(1);
    }

    public function testInsertAt()
    {
        $x = (new \XMLElement('xml'))
            ->appendChild((new \XMLElement('child'))->setValue('1'))
            ->appendChild((new \XMLElement('child'))->setValue('2'))
            ->appendChild((new \XMLElement('child'))->setValue('3'))
            ->removeChildAt(1);
        $this->assertNotEmpty($x->getChildren());
        $this->assertEquals(2, $x->getNumberOfChildren());
        $this->assertEquals('<xml><child>1</child><child>3</child></xml>', $x->generate());
    }

    /**
     * @expectedException Exception
     */
    public function testInvalidInsertAt()
    {
        $x = (new \XMLElement('xml'));
        $x->insertChildAt(2, $x);
    }

    public function testReplaceAt()
    {
        $x = (new \XMLElement('xml'))
            ->appendChild((new \XMLElement('child'))->setValue('1'))
            ->appendChild((new \XMLElement('child'))->setValue('2'))
            ->appendChild((new \XMLElement('child'))->setValue('3'))
            ->replaceChildAt(1, (new \XMLElement('child'))->setValue('4'));
        $this->assertNotEmpty($x->getChildren());
        $this->assertEquals(3, $x->getNumberOfChildren());
        $this->assertEquals('<xml><child>1</child><child>4</child><child>3</child></xml>', $x->generate());
    }

    /**
     * @expectedException Exception
     */
    public function testInvalidReplaceAt()
    {
        $x = (new \XMLElement('xml'));
        $x->removeChildAt(2, $x);
    }

    public function testFromXMLString()
    {
        $xml = '<xml test="xml-string"><child>1</child><child>4</child><child>3</child></xml>';
        $x = \XMLElement::fromXMLString($xml);
        $this->assertNotEmpty($x);
        $this->assertNotEmpty($x->getChildren());
        $this->assertEquals(3, $x->getNumberOfChildren());
        $this->assertEquals('xml', $x->getName());
        $this->assertEquals('xml-string', $x->getAttribute('test'));
        $this->assertEquals('4', $x->getChild(1)->getValue());
    }

    public function testConvertFromXMLString()
    {
        $xml = '<xml test="xml-string"><child>1</child><child>4</child><child>3</child></xml>';
        $x = \XMLElement::convertFromXMLString('xml-test', $xml);
        $this->assertNotEmpty($x);
        $this->assertNotEmpty($x->getChildren());
        $this->assertEquals(3, $x->getNumberOfChildren());
        $this->assertEquals('xml-test', $x->getName());
        $this->assertEquals('xml-string', $x->getAttribute('test'));
        $this->assertEquals('4', $x->getChild(1)->getValue());
    }

    public function testFromDOMDocument()
    {
        $xml = '<xml test="dom-doc"><child>1</child><child>4</child><child>3</child></xml>';
        $doc = new \DOMDocument();
        $doc->loadXML($xml);
        $x = \XMLElement::fromDOMDocument($doc);
        $this->assertTrue($x instanceof \XMLElement);
        $this->assertFalse($x instanceof \XMLDocument);
        $this->assertNotEmpty($x);
        $this->assertNotEmpty($x->getChildren());
        $this->assertEquals(3, $x->getNumberOfChildren());
        $this->assertEquals('xml', $x->getName());
        $this->assertEquals('dom-doc', $x->getAttribute('test'));
        $this->assertEquals('4', $x->getChild(1)->getValue());
    }

    public function testConvertFromDOMDocument()
    {
        $xml = '<xml test="dom-doc"><child>1</child><child>4</child><child>3</child></xml>';
        $doc = new \DOMDocument();
        $doc->loadXML($xml);
        $x = \XMLElement::convertFromDOMDocument('xml-test', $doc);
        $this->assertNotEmpty($x);
        $this->assertNotEmpty($x->getChildren());
        $this->assertEquals(3, $x->getNumberOfChildren());
        $this->assertEquals('xml-test', $x->getName());
        $this->assertEquals('dom-doc', $x->getAttribute('test'));
        $this->assertEquals('4', $x->getChild(1)->getValue());
    }
}
