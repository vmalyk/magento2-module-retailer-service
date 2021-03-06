<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\RetailerService
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\RetailerService\Model\ResourceModel;

use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Stdlib\DateTime;
use Smile\RetailerService\Api\Data\ServiceInterface;
use Smile\RetailerService\Model\ServiceMediaUpload;

/**
 * Service Resource Model
 *
 * @category Smile
 * @package  Smile\RetailerService
 * @author   Fanny DECLERCK <fadec@smile.fr>
 */
class Service extends AbstractDb
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var MetadataPool
     */
    protected $metadataPool;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var ServiceMediaUpload
     */
    protected $mediaUpload;

    /**
     * Service constructor.
     *
     * @param Context            $context        Application Context
     * @param EntityManager      $entityManager  Entity Manager
     * @param MetadataPool       $metadataPool   Metadata Pool
     * @param DateTime           $dateTime       Datetime
     * @param ServiceMediaUpload $mediaUpload    Media upload
     * @param string             $connectionName Connection name
     */
    public function __construct(
        Context $context,
        EntityManager $entityManager,
        MetadataPool $metadataPool,
        DateTime $dateTime,
        ServiceMediaUpload $mediaUpload,
        $connectionName = null
    ) {
        $this->entityManager = $entityManager;
        $this->metadataPool  = $metadataPool;
        $this->dateTime      = $dateTime;
        $this->mediaUpload   = $mediaUpload;

        parent::__construct($context, $connectionName);
    }

    /**
     * {@inheritDoc}
     */
    public function getConnection()
    {
        return $this->metadataPool->getMetadata(ServiceInterface::class)->getEntityConnection();
    }

    /**
     * Load an service by a given field's value.
     *
     * @param \Magento\Framework\Model\AbstractModel $object The service
     * @param mixed                                  $value  The value
     * @param null                                   $field  The field, if any
     *
     * @return $this
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function load(AbstractModel $object, $value, $field = null)
    {
        $serviceId = $this->getServiceId($object, $value, $field);
        if ($serviceId) {
            $this->entityManager->load($object, $serviceId);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function save(AbstractModel $object)
    {
        foreach ([ServiceInterface::CREATED_AT, ServiceInterface::END_AT] as $field) {
            $value = !$object->getData($field) ? null : $this->dateTime->formatDate($object->getData($field));
            $object->setData($field, $value);
        }

        $this->entityManager->save($object);
        $this->mediaUpload->removeMediaFromTmp($object);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(AbstractModel $object)
    {
        $this->entityManager->delete($object);
        $this->mediaUpload->removeMedia($object);

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName) Method is inherited.
     */
    protected function _construct()
    {
        $this->_init(
            ServiceInterface::TABLE_NAME,
            ServiceInterface::SERVICE_ID
        );
    }

    /**
     * Retrieve service Id by field value
     *
     * @param \Magento\Framework\Model\AbstractModel $object The service
     * @param mixed                                  $value  The value
     * @param null                                   $field  The field
     *
     * @return int|false
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getServiceId(AbstractModel $object, $value, $field = null)
    {
        $entityMetadata = $this->metadataPool->getMetadata(ServiceInterface::class);
        if ($field === null) {
            $field = ServiceInterface::SERVICE_ID;
        }

        $entityId = $value;

        if ($field != $entityMetadata->getIdentifierField()) {
            $select = $this->_getLoadSelect($field, $value, $object);
            $select->reset(Select::COLUMNS)
                ->columns($this->getMainTable() . '.' . $entityMetadata->getIdentifierField())
                ->limit(1);

            $result = $this->getConnection()->fetchCol($select);
            $entityId = count($result) ? $result[0] : false;
        }

        return $entityId;
    }
}
