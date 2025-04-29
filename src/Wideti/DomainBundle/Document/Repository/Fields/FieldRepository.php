<?php

namespace Wideti\DomainBundle\Document\Repository\Fields;

use Composer\SelfUpdate\Keys;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Wideti\DomainBundle\Document\CustomFields\Field;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Helpers\StringHelper;

/**
 * Class FieldRepository
 * @package Wideti\DomainBundle\Document\Repository\Fields
 */
class FieldRepository extends DocumentRepository
{
    /**
     * @return mixed
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function getLoginField()
    {
        $qb = $this->createQueryBuilder()
            ->select()
            ->field('isLogin')->equals(true)
            ;
        return $qb->getQuery()->execute();
    }

    /**
     * @return mixed
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function findSignUpFields($apGroupId = [0])
    {
        $qb = $this->createQueryBuilder()
            ->select()
            ->sort('position', 1);
        $qb->addOr(
            $qb->expr()->field('groupId')->exists(false),
            $qb->expr()->field('groupId')->equals([]),
            $qb->expr()->field('groupId')->in($apGroupId)
        );
        return $qb->getQuery()->execute();
    }

    /**
     * @param $properties
     * @return mixed
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function getRandomFields($properties)
    {
        $fields = $this->findSignUpFields();

        $arrayFields = [];

        foreach ($fields as $field) {
            array_push($arrayFields, $field->getIdentifier());
        }

        unset($properties[key(array_diff($properties, $arrayFields))]);

        $qb = $this->createQueryBuilder()
            ->select()
            ->field('identifier')->equals($properties[array_rand($properties)])
            ;

        return $qb->getQuery()->execute();
    }

    /**
     * @param $field
     * @return bool
     */
    public function hasField($field)
    {
        $qb = $this->createQueryBuilder();

        $qb->field('identifier')->equals($field);

        return boolval($qb->getQuery()->count());
    }

    private function hasDiference($existingDocument, $newDocument, $fieldsToIgnore) {
        $existingData = json_decode(json_encode($existingDocument), true);
        $newData = json_decode(json_encode($newDocument), true);

        // Remove campos ignorados
        foreach ($fieldsToIgnore as $field) {
            unset($existingData[$field], $newData[$field]);
        }

        // Compara os arrays
        return $existingData != $newData;
    }

    public function saveAll(array $fields, Client $client)
    {
        $position         = 10;
        $database         = $this->setClientDatabase($client);
        $fieldsCollection = $this->setCollection($database, "fields");
        $guestsCollection = $this->setCollection($database, "guests");

        $defaultIndexs = [
            '_id',
            'mysql',
            'created',
            'social.id'
        ];
        $defaultIndexSet = array_flip($defaultIndexs);

        $indexes = $guestsCollection->getIndexInfo();
        $currentIndexKeys = array_column($indexes, 'key');

        $currentIndexSet = [];
        foreach ($currentIndexKeys as $index) {
                $currentIndexSet[key($index)] = true;
        }

        foreach ($indexes as $index) {
            $indexName = $index['name'];
            $indexKey = key($index['key']);

            if (isset($defaultIndexSet[$indexKey])) {
                continue;
            }

            $shouldRemove = true;
            foreach ($fields as $field) {
                if (strpos($indexName, $field->getIdentifier()) !== false) {
                    if ($field->getIsUnique()) {
                        $shouldRemove = false;
                    }
                    break;
                }
            }
            if ($shouldRemove) {
                $guestsCollection->deleteIndex($index['key']);
            }
        }

        foreach ($defaultIndexs as $defaultIndex) {
            if (!isset($currentIndexSet[$defaultIndex])) {
                $guestsCollection->createIndex([$defaultIndex => 1]);
            }
        }

        foreach ($fields as $field) {
            $indexName = $field->getIdentifier();
            $indexExists = false;
            foreach ($currentIndexKeys as $existingIndex) {
                if (strpos(key($existingIndex), $indexName) !== false) {
                    $indexExists = true;
                    break;
                }
            }
            if (!$indexExists) {
                $this->createUniqueIndexForField($field, $guestsCollection);
            }
        }

        $newFieldsIdentifiers = [];

        /**
         * @param Field
         */
        foreach ($fields as $field) {
            $newFieldsIdentifiers[] = $field->getIdentifier();
            $existentField = $this->findOneBy(['identifier' => $field->getIdentifier()]);
            if ($existentField) {
                $field->setPosition($position);
                if ($this->hasDiference($existentField, $field, ["_id"])) {
                    $field->getIsLogin() ?: $field->setIsLogin(false);
                    $existentField->setIsLogin($field->getIsLogin());
                    $existentField->setValidations($field->getValidations());
                    $existentField->setPosition($field->getPosition());
                    $existentField->setOnAccess($field->getOnAccess());
                    $existentField->setIsUnique($field->getIsUnique());
                    $this->getDocumentManager()->persist($existentField);
                }
            }  else {
                $field->setId(null);
                $field->getIsLogin() ?: $field->setIsLogin(false);
                $field->setPosition($position);

                $this->getDocumentManager()->persist($field);
            }
            $position += 10;
        }
        $oldFields =  $this->findAll();
        /**
        * @param Field
        */
        foreach ($oldFields as $oldField) {
            if (!in_array($oldField->getIdentifier(), $newFieldsIdentifiers)) {
                $this->getDocumentManager()->remove($oldField);
            }
        }
        $this->getDocumentManager()->flush();
    }

    /**
     * @return \MongoDB
     */
    private function setClientDatabase(Client $client)
    {
        $domain = $client->getDomain();
        if(!strpos($domain, "wspot.com.br") || !strpos($domain, "mambowifi")) {
            $domain = StringHelper::slugDomain($client->getDomain());
        }

        return $this->getDocumentManager()->getConnection()
            ->getMongoClient()
            ->selectDB($domain);
    }

    /**
     * @param \MongoDB $database
     * @param $collectionName
     * @return mixed
     */
    private function setCollection($database, $collectionName)
    {
        return $database->selectCollection($collectionName);
    }

    /**
     * @param Field $field
     * @param \MongoCollection $collection
     */
    private function createUniqueIndexForField(Field $field, \MongoCollection $collection)
    {
        if ($field->getIsUnique()) {
            $fieldName = "properties." . $field->getIdentifier();
            $indexName = "properties_" . $field->getIdentifier() . "_1";

            $collection->createIndex(
                [$fieldName => 1],
                [
                    'unique'   => true,
                    'name'     => $indexName,
                    'sparse'   => true
                ]
            );
        }
    }

    /**
     * @return mixed
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function loadCustomFieldName()
    {
        return $this->createQueryBuilder()->select("identifier")->getQuery()->execute();
    }
}
