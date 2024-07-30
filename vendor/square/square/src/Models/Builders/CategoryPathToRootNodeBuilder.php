<?php

declare(strict_types=1);

namespace Square\Models\Builders;

use Core\Utils\CoreHelper;
use Square\Models\CategoryPathToRootNode;

/**
 * Builder for model CategoryPathToRootNode
 *
 * @see CategoryPathToRootNode
 */
class CategoryPathToRootNodeBuilder
{
    /**
     * @var CategoryPathToRootNode
     */
    private $instance;

    private function __construct(CategoryPathToRootNode $instance)
    {
        $this->instance = $instance;
    }

    /**
     * Initializes a new category path to root node Builder object.
     */
    public static function init(): self
    {
        return new self(new CategoryPathToRootNode());
    }

    /**
     * Sets category id field.
     */
    public function categoryId(?string $value): self
    {
        $this->instance->setCategoryId($value);
        return $this;
    }

    /**
     * Unsets category id field.
     */
    public function unsetCategoryId(): self
    {
        $this->instance->unsetCategoryId();
        return $this;
    }

    /**
     * Sets category name field.
     */
    public function categoryName(?string $value): self
    {
        $this->instance->setCategoryName($value);
        return $this;
    }

    /**
     * Unsets category name field.
     */
    public function unsetCategoryName(): self
    {
        $this->instance->unsetCategoryName();
        return $this;
    }

    /**
     * Initializes a new category path to root node object.
     */
    public function build(): CategoryPathToRootNode
    {
        return CoreHelper::clone($this->instance);
    }
}
