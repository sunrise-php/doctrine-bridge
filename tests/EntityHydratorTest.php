<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests;

use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

class EntityHydratorTest extends TestCase
{
    use Fixtures\EntityManagerRegistryAwareTrait;

    public function testInitObject() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $this->assertInstanceOf(
            Fixtures\Entity\Common\Post::class,
            $hydrator->hydrate(Fixtures\Entity\Common\Post::class, [])
        );
    }

    public function testReinitObject() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $object = new Fixtures\Entity\Common\Post();

        $this->assertSame($object, $hydrator->hydrate($object, []));
    }

    /**
     * @dataProvider invalidObjectProvider
     */
    public function testInitInvalidObject($invalidObject) : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $this->expectException(InvalidArgumentException::class);

        $hydrator->hydrate($invalidObject, []);
    }

    public function invalidObjectProvider() : array
    {
        return [
            ['NonexistenceClass'],
            [Fixtures\Entity\Common\Uninitiable::class],
            [\stdClass::class],
            [null],
            [false],
            [0],
            [0.0],
            [''],
            [[]],
            [new \stdClass],
            [function () {
            }],
        ];
    }

    /**
     * @dataProvider booleanValueProvider
     */
    public function testHydrateBooleanField($falseValue, $trueValue) : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'isDisabled' => $falseValue,
            'isVerified' => $trueValue,
        ];

        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertFalse($object->isDisabled());
        $this->assertTrue($object->isVerified());
    }

    public function booleanValueProvider() : array
    {
        return [
            [false, true],
            [0, 1],
            ['0', '1'],
            ['false', 'true'],
            ['no', 'yes'],
            ['off', 'on'],
        ];
    }

    /**
     * @dataProvider integerValueProvider
     */
    public function testHydrateIntegerField($value) : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'hits' => $value,
        ];

        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertSame(101, $object->getHits());
    }

    public function integerValueProvider() : array
    {
        return [
            [101],
            [101.0],
            ['101'],
        ];
    }

    /**
     * @dataProvider floatValueProvider
     */
    public function testHydrateFloatField($value) : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'score' => $value,
        ];

        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertSame(25.75, $object->getScore());
    }

    public function floatValueProvider() : array
    {
        return [
            [25.75],
            ['25.75'],
        ];
    }

    public function testHydrateStringFields() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'name' => 'cfe0ecb0-d856-48b3-8541-7b89159782d9',
            'summary' => 'bf031afd-387c-4172-92fb-139268d8b6bf',
        ];

        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertSame($data['name'], $object->getName());
        $this->assertSame($data['summary'], $object->getSummary());
    }

    /**
     * @dataProvider dataTimeImmutableValueProvider
     */
    public function testHydrateDateTimeImmutableField($value) : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'updatedAt' => $value,
        ];

        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertSame('2010-01-01', $object->getUpdatedAt()->format('Y-m-d'));
    }

    public function dataTimeImmutableValueProvider() : array
    {
        return [
            [new \DateTimeImmutable('2010-01-01')],
            ['2010-01-01'],
            ['1262304000'],
            [1262304000],
        ];
    }

    /**
     * @dataProvider dateTimeValueProvider
     */
    public function testHydrateDateTimeField($value) : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'enablesAt' => $value,
        ];

        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertSame('2010-01-01', $object->getEnablesAt()->format('Y-m-d'));
    }

    public function dateTimeValueProvider() : array
    {
        return [
            [new \DateTime('2010-01-01')],
            ['2010-01-01'],
            ['1262304000'],
            [1262304000],
        ];
    }

    /**
     * @dataProvider dateIntervalValueProvider
     */
    public function testHydrateDateIntervalField($value) : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'someInterval' => $value,
        ];

        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertSame(68, $object->getSomeInterval()->y);
    }

    public function dateIntervalValueProvider() : array
    {
        return [
            [\date_diff(\date_create('1970-01-01'), \date_create('2038-01-19'))],
            [['start' => '1970-01-01', 'end' => '2038-01-19']],
            [['start' => 0, 'end' => 2147472000]],
            ['1970-01-01 - 2038-01-19'],
            ['P68Y'],
        ];
    }

    public function testHydrateUnhydrableField() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'unhydrableValue' => 'some value',
        ];

        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertSame('', $object->getUnhydrableValue());
    }

    public function testHydrateUnhydrableAssociation() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'updatedBy' => [
                'name' => '28fb2188-01d5-4654-9992-6f0f3b952e93',
            ],
        ];

        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertNull($object->getUpdatedBy());
    }

    public function testHydrateId() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'id' => 'a3c6bccf-a8a8-45ed-9c07-2e50c0519021',
        ];

        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertNotSame($data['id'], $object->getId());
    }

    public function testHydrateUnsetableField() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'createdAt' => '1970-01-01 00:00:00',
        ];

        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertNotSame($data['createdAt'], $object->getCreatedAt()->format('Y-m-d H:i:s'));
    }

    public function testHydrateOneAssociation() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'category' => [
                'name' => 'ca4962ba-4faf-425c-b436-f3abd6fdfe89',
                'summary' => 'e223c8d9-99de-49d9-adfd-e5ffd0677873',
            ],
        ];

        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertNotNull($object->getCategory());

        $this->assertSame($data['category']['name'], $object->getCategory()->getName());
        $this->assertSame($data['category']['summary'], $object->getCategory()->getSummary());
    }

    public function testHydrateManyAssociationsWithOne() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'tags' => [
                [
                    'name' => 'e4ddb0ae-bb87-424b-a467-3ee4777e9ee6',
                    'summary' => '2219d4b6-0517-4c93-9698-533d16e89269',
                ],
            ],
        ];

        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertCount(1, $object->getTags());

        $this->assertSame($data['tags'][0]['name'], $object->getTags()->offsetGet(0)->getName());
        $this->assertSame($data['tags'][0]['summary'], $object->getTags()->offsetGet(0)->getSummary());
    }

    public function testHydrateManyAssociationsWithSeveral() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'tags' => [
                [
                    'name' => '37735afe-30e7-45ba-bb1a-306d872669e9',
                    'summary' => 'f1e34d6d-44a2-40f5-a50e-864755fb3d82',
                ],
                [
                    'name' => 'e52e2226-7187-4d9b-930a-ddd09291caac',
                    'summary' => '56be736b-12a4-4cd3-bfac-971a2e3e7fea',
                ],
            ],
        ];

        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertCount(2, $object->getTags());

        $this->assertSame($data['tags'][0]['name'], $object->getTags()->offsetGet(0)->getName());
        $this->assertSame($data['tags'][0]['summary'], $object->getTags()->offsetGet(0)->getSummary());

        $this->assertSame($data['tags'][1]['name'], $object->getTags()->offsetGet(1)->getName());
        $this->assertSame($data['tags'][1]['summary'], $object->getTags()->offsetGet(1)->getSummary());
    }

    public function testHydrateManyAssociationsWithNull() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'tags' => null,
        ];

        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertEmpty($object->getTags());
    }

    public function testHydrateManyAssociationsWithNulls() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'tags' => [null],
        ];

        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertEmpty($object->getTags());
    }

    public function testHydrateOneAssociationUsingId() : void
    {
        $registry = $this->getEntityManagerRegistry();

        $category = new Fixtures\Entity\Common\Category();
        $category->setName('foo');
        $category->setSummary('Lorem ipsum');

        $registry->getManager()->persist($category);
        $registry->getManager()->flush();

        $data = [
            'category' => $category->getid(),
        ];

        $object = $registry->getHydrator()->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertSame($category, $object->getCategory());
    }

    public function testHydrateManyAssociationsUsingId() : void
    {
        $registry = $this->getEntityManagerRegistry();

        $tag = new Fixtures\Entity\Common\Tag();
        $tag->setName('foo');
        $tag->setSummary('Lorem ipsum');

        $registry->getManager()->persist($tag);
        $registry->getManager()->flush();

        $data = [
            'tags' => [
                $tag->getId(),
            ],
        ];

        $object = $registry->getHydrator()->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertSame([$tag], $object->getTags()->getValues());
    }

    public function testHydrateManyAssociationsUsingIds() : void
    {
        $registry = $this->getEntityManagerRegistry();

        $tag1 = new Fixtures\Entity\Common\Tag();
        $tag1->setName('foo');
        $tag1->setSummary('Lorem ipsum');

        $tag2 = new Fixtures\Entity\Common\Tag();
        $tag2->setName('bar');
        $tag2->setSummary('Lorem ipsum');

        $registry->getManager()->persist($tag1);
        $registry->getManager()->persist($tag2);
        $registry->getManager()->flush();

        $data = [
            'tags' => [
                $tag1->getId(),
                $tag2->getId(),
            ],
        ];

        $object = $registry->getHydrator()->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertSame([$tag1, $tag2], $object->getTags()->getValues());
    }

    public function testHydrateNullableFieldWithNull() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'nullableValue' => null,
        ];

        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertNull($object->getNullableValue());
    }

    public function testHydrateUnnullableFieldWithNull() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'unnullableValue' => null,
        ];

        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertNotNull($object->getUnnullableValue());
    }

    /**
     * @dataProvider anyTypesProvider
     */
    public function testHydrateUntypedField($value) : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'untypedValue' => $value,
        ];

        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertSame($value, $object->getUntypedValue());
    }

    public function anyTypesProvider() : array
    {
        return [
            [null],
            [false],
            [0],
            [0.0],
            [''],
            [[]],
            [new \stdClass],
            [function () {
            }],
            [\STDIN],
        ];
    }

    public function testHydrateUnsetableAssociation() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'unsetableAssociation' => [
                'name' => '0b935aa7-676e-4e32-8e90-46c99b5630bf',
                'summary' => '55b87ed8-c471-4ff4-a50d-bbdf205b0bc2',
            ],
        ];

        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertNull($object->getUnsetableAssociation());
    }

    public function testHydrateNullableAssociation() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'nullableAssociation' => null,
        ];

        $post = new Fixtures\Entity\Common\Post();
        $category = new Fixtures\Entity\Common\Category();
        $post->setNullableAssociation($category);

        $hydrator->hydrate($post, $data);

        $this->assertNull($post->getNullableAssociation());
    }

    public function testHydrateUnnullableAssociation() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'unnullableAssociation' => null,
        ];

        $post = new Fixtures\Entity\Common\Post();
        $category = new Fixtures\Entity\Common\Category();
        $post->setUnnullableAssociation($category);

        $hydrator->hydrate($post, $data);

        $this->assertNotNull($post->getUnnullableAssociation());
    }

    public function testHydrateUnaddableAssociation() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'unaddableAssociation' => [
                'name' => '1d0570e7-5f9c-4389-9ed5-a767ba9e15e2',
                'summary' => '40a320a1-5455-4d62-8fc5-283ca71a45fe',
            ],
        ];

        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertCount(0, $object->getUnaddableAssociation());
    }

    public function testHydrateUnhydrableFieldMarkedThroughAnnotationOnly() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();
        $hydrator->useAnnotations();
        $hydrator->useAnnotations(); // for CC

        $data = ['unhydrableValueMarkedThroughAnnotationOnly' => 'foo'];

        $this->assertEmpty($hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data)
                ->getUnhydrableValueMarkedThroughAnnotationOnly());
    }

    public function testPrivateSetter() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'privateValue' => '0023352c-4644-4020-92c7-2a5181749c0c',
        ];

        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertEmpty($object->getPrivateValue());
    }

    public function testUnparameterizedSetter() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'unparameterizedValue' => '0a77cdc4-c5bc-4a4c-b462-3cf650f17c96',
        ];

        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertEmpty($object->getUnparameterizedValue());
    }

    public function testPrivateAdder() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'privateAssociation' => [
                [
                    'name' => '6376644c-56d6-4a59-bc5f-6057896bb5d7',
                ],
            ],
        ];

        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertEmpty($object->getPrivateAssociation());
    }

    public function testUnparameterizedAdder() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'unparameterizedAssociation' => [
                [
                    'name' => '6376644c-56d6-4a59-bc5f-6057896bb5d7',
                ],
            ],
        ];

        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertEmpty($object->getUnparameterizedAssociation());
    }

    public function testSnakeCasedField() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = ['snake_cased_value' => '2ca57056-a31b-4569-8af1-bb5e53ebe1f7'];
        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertNotEmpty($object->getSnakeCasedValue());
    }

    public function testArrayableField() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = ['arrayValue' => ['adc07152-0a67-4b4d-b781-9289d5afc8a3']];
        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertSame($data['arrayValue'], $object->getArrayValue());
    }

    public function testObjectableField() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = ['objectValue' => (object) ['foo' => 'adc07152-0a67-4b4d-b781-9289d5afc8a3']];
        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertSame($data['objectValue'], $object->getObjectValue());
    }

    public function testInvalidDateTime() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $dt = new \DateTime('1 day ago');
        $post = new Fixtures\Entity\Common\Post();
        $post->setEnablesAt($dt);
        $hydrator->hydrate($post, ['enablesAt' => 0.0]);

        $this->assertSame($dt, $post->getEnablesAt());
    }

    public function testInvalidDateTimeImmutable() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $dt = new \DateTimeImmutable('1 day ago');
        $post = new Fixtures\Entity\Common\Post();
        $post->setUpdatedAt($dt);
        $hydrator->hydrate($post, ['updatedAt' => 0.0]);

        $this->assertSame($dt, $post->getUpdatedAt());
    }

    public function testInvalidDateInterval() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $di = new \DateInterval('PT1S');
        $post = new Fixtures\Entity\Common\Post();
        $post->setSomeInterval($di);

        $hydrator->hydrate($post, ['someInterval' => 0.0]);
        $this->assertSame($di, $post->getSomeInterval());

        $hydrator->hydrate($post, ['someInterval' => 'P1X']);
        $this->assertSame($di, $post->getSomeInterval());
    }

    public function testUnsupportedType()
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = ['unsupportedTypeValue' => 'ee3c5c4b-4d81-4f9a-9a8b-c55db5f0249a'];
        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertEmpty($object->getUnsupportedTypeValue());
    }

    public function testMixedType()
    {
        if (8 > \PHP_MAJOR_VERSION) {
            $this->markTestSkipped('PHP 8 is required...');
            return;
        }

        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = ['mixedTypeValue' => 'ac790ff7-ec0f-4431-8192-f39fc681ef74'];
        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertSame($data['mixedTypeValue'], $object->getMixedTypeValue());
    }

    public function testUnionType()
    {
        if (8 > \PHP_MAJOR_VERSION) {
            $this->markTestSkipped('PHP 8 is required...');
            return;
        }

        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator('bar');

        $object = $hydrator->hydrate(Fixtures\Entity\PHP80\User::class, [
            'password' => 'b2ef8ea9-2bfe-40d5-92ae-769c658ac6fb',
        ]);

        $this->assertNotEmpty($object->getPassword());
    }

    public function testHydrateManyAssociationsUsingNotArray() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'tags' => new \stdClass,
        ];

        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertCount(0, $object->getTags());
    }

    public function testHydrateManyAssociationsUsingNotList() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'tags' => [
                'foo' => [
                    'name' => '37735afe-30e7-45ba-bb1a-306d872669e9',
                    'summary' => 'f1e34d6d-44a2-40f5-a50e-864755fb3d82',
                ],
                'bar' => [
                    'name' => 'e52e2226-7187-4d9b-930a-ddd09291caac',
                    'summary' => '56be736b-12a4-4cd3-bfac-971a2e3e7fea',
                ],
            ],
        ];

        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertCount(0, $object->getTags());
    }
}
