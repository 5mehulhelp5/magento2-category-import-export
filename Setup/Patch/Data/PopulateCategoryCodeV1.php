<?php
/**
 * Copyright © OpenGento, All rights reserved.
 * See LICENSE bundled with this library for license details.
 */
declare(strict_types=1);

namespace Opengento\CategoryImportExport\Setup\Patch\Data;

use Exception;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category as ResourceCategory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\Setup\Patch\DataPatchInterface;

use function str_replace;
use function strtolower;

class PopulateCategoryCodeV1 implements DataPatchInterface
{
    public function __construct(
        private CollectionFactory $collecionFactory,
        private ResourceCategory $resourceCategory
    ) {}

    public static function getDependencies(): array
    {
        return [AddCategoryCodeAttributeV1::class];
    }

    public function getAliases(): array
    {
        return [];
    }

    /**
     * @throws Exception
     */
    public function apply(): self
    {
        $collection = $this->collecionFactory->create();
        $categories = $collection->addAttributeToSelect(['entity_id', 'name', 'category_code', 'path'])->getItems();

        $idsToName = [];
        /** @var Category $category */
        foreach ($categories as $category) {
            $idsToName[$category->getId()] = $category->getName();
        }

        /** @var Category $category */
        foreach ($categories as $category) {
            if (!$category->getData('category_code')) {
                $code = [];
                foreach ($category->getPathIds() as $pathId) {
                    if ($pathId === '') {
                        throw new LocalizedException(
                            new Phrase(
                                'Category "%1" has an invalid path: %2.',
                                [$category->getName(), $category->getPath()]
                            )
                        );
                    }
                    $code[] = $idsToName[$pathId];
                }

                $category->setCustomAttribute('category_code', strtolower(str_replace(' ', '_', implode('_', $code))));
                $this->resourceCategory->saveAttribute($category, 'category_code');
            }
        }

        return $this;
    }
}
