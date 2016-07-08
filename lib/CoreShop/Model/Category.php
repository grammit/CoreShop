<?php
/**
 * CoreShop.
 *
 * LICENSE
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2016 Dominik Pfaffenbauer (http://www.pfaffenbauer.at)
 * @license    http://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace CoreShop\Model;

use CoreShop\Exception\UnsupportedException;
use Pimcore\Model\Object;
use Pimcore\Model\Asset\Image;

/**
 * Class Category
 * @package CoreShop\Model
 */
class Category extends Base
{
    /**
     * Pimcore Object Class.
     *
     * @var string
     */
    public static $pimcoreClass = 'Pimcore\\Model\\Object\\CoreShopCategory';

    /**
     * Get all Categories.
     *
     * @return array
     */
    public static function getAll()
    {
        $list = self::getList();

        return $list->getObjects();
    }

    /**
     * Get first level of categories.
     *
     * @return array
     */
    public static function getFirstLevel()
    {
        $list = self::getList();
        $list->setCondition("parentCategory__id is null AND shops LIKE '%,".Shop::getShop()->getId().",%'");

        return $list->getObjects();
    }

    /**
     * Returns all Child Categories from $category.
     *
     * @param Category $category
     *
     * @return array
     */
    public static function getAllChildCategories(Category $category)
    {
        $allChildren = array($category->getId());

        $loopChilds = function (Category $child) use (&$loopChilds, &$allChildren) {
            $childs = $child->getChildCategories();

            foreach ($childs as $child) {
                $allChildren[] = $child->getId();

                $loopChilds($child);
            }
        };

        $loopChilds($category);

        return $allChildren;
    }

    /**
     * Get Products from the Category.
     *
     * @param bool $includeChildCategories
     *
     * @return array
     */
    public function getProducts($includeChildCategories = false)
    {
        $list = Product::getList();

        if (!$includeChildCategories) {
            $list->setCondition("enabled = 1 AND categories LIKE '%,".$this->getId().",%'");
        } else {
            $categories = $this->getCatChilds();
            $categoriesWhere = array();

            foreach ($categories as $cat) {
                $categoriesWhere[] = "categories LIKE '%,".$cat.",%'";
            }

            $list->setCondition('enabled = 1 AND ('.implode(' OR ', $categoriesWhere).')');
        }

        return $list->getObjects();
    }

    /**
     * Get Products from the Category with Paging.
     *
     * @param int   $page
     * @param int   $itemsPerPage
     * @param array $sort
     * @param bool  $includeChildCategories
     *
     * @return \Zend_Paginator
     *
     * @throws \Zend_Paginator_Exception
     */
    public function getProductsPaging($page = 0, $itemsPerPage = 10, $sort = array('name' => 'name', 'direction' => 'asc'), $includeChildCategories = false)
    {
        $list = Product::getList();

        $condition = "enabled = 1";

        if (!$includeChildCategories) {
            $condition .= " AND categories LIKE '%,".$this->getId().",%'";
        } else {
            $categories = $this->getCatChilds();
            $categoriesWhere = array();

            foreach ($categories as $cat) {
                $categoriesWhere[] = "categories LIKE '%,".$cat.",%'";
            }

            $condition .= ' AND ('.implode(' OR ', $categoriesWhere).')';
        }

        $condition .= " AND shops LIKE '%,".Shop::getShop()->getId().",%'";

        $list->setCondition($condition);
        $list->setOrderKey($sort['name']);
        $list->setOrder($sort['direction']);

        $paginator = \Zend_Paginator::factory($list);
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage($itemsPerPage);

        return $paginator;
    }

    /**
     * Checks if category is child of hierachy.
     *
     * @param Category $category
     * @param int      $level    to check hierachy (0 = topMost)
     *
     * @return bool
     */
    public function inCategory(Category $category, $level = 0)
    {
        $mostTop = $this->getHierarchy();
        $mostTop = $mostTop[$level];

        $childs = self::getAllChildCategories($mostTop);

        return in_array($category->getId(), $childs);
    }

    /**
     * Get Level of Category.
     *
     * @return int
     */
    public function getLevel()
    {
        return count($this->getHierarchy());
    }

    /**
     * Returns all Children from this Category.
     *
     * @return array
     */
    public function getCatChilds()
    {
        return self::getAllChildCategories($this);
    }

    /**
     * Get default image.
     *
     * @return bool|\Pimcore\Model\Asset
     */
    public function getDefaultImage()
    {
        $defaultImage = Configuration::get('SYSTEM.CATEGORY.DEFAULTIMAGE');

        if ($defaultImage) {
            $image = Image::getByPath($defaultImage);

            if ($image instanceof Image) {
                return $image;
            }
        }

        return false;
    }

    /**
     * Get image.
     *
     * @return bool|\Pimcore\Model\Asset
     */
    public function getImage()
    {
        if ($this->getCategoryImage() instanceof Image) {
            return $this->getCategoryImage();
        }

        return $this->getDefaultImage();
    }

    /**
     * Get Category hierarchy.
     *
     * @return Category[]
     */
    public function getHierarchy()
    {
        $hierarchy = array();

        $category = $this;

        do {
            $hierarchy[] = $category;

            $category = $category->getParentCategory();
        } while ($category instanceof self);

        return array_reverse($hierarchy);
    }

    /**
     * Get all child Categories.
     *
     * @return array
     */
    public function getChildCategories()
    {
        $list = Category::getList();
        $list->setCondition("parentCategory__id = ? AND shops LIKE '%,".Shop::getShop()->getId().",%'", array($this->getId()));

        return $list->getObjects();
    }

    /**
     * returns category image
     * this method has to be overwritten in Pimcore Object.
     *
     * @throws UnsupportedException
     *
     * @return Image
     */
    public function getCategoryImage()
    {
        throw new UnsupportedException('getCategoryImage is not supported for '.get_class($this));
    }

    /**
     * returns parent category
     * this method has to be overwritten in Pimcore Object.
     *
     * @throws UnsupportedException
     *
     * @return Category
     */
    public function getParentCategory()
    {
        throw new UnsupportedException('getParentCategory is not supported for '.get_class($this));
    }
}
