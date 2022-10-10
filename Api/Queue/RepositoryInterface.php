<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Api\Queue;

use Magento\Framework\Api\SearchCriteriaInterface;

interface RepositoryInterface
{
    /**
     * @param int $id
     * @return mixed
     */
    public function getById(int $id);

    /**
     * @param DataInterface $item
     * @return mixed
     */
    public function save(DataInterface $item);

    /**
     * @param DataInterface $item
     * @return mixed
     */
    public function delete(DataInterface $item);

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return mixed
     */
    public function getList(SearchCriteriaInterface $searchCriteria);
}
