
        if (\PHP_VERSION_ID >= 80000 && $attributes = $reflectionClass->getAttributes(Resource::class, \ReflectionAttribute::IS_INSTANCEOF)) {
            return $this->createAttributesMetadata($attributes, $parentResourceMetadata);
        }

    private function createAttributesMetadata(array $attributes, ResourceMetadata $parentResourceMetadata = null): ResourceMetadata
    {
        $collectionOperations = $itemOperations = [];
        $default = new Resource();
        $attributes = [];

        foreach ($attributes as $attribute) {
            /** @var Resource **/
            $resource = $attribute->newInstance();

            // these are defaults
            if ($attribute->getName() === Resource::class) {
                $default = $resource;
                continue;
            }

            foreach ($this->defaults['attributes'] as $key => $value) {
                // $attribute->extraProperty
                // if (!isset($attributes[$key])) {
                //     $attributes[$key] = $value;
                // }
            }

            dd($attribute);
        }

        if (!$parentResourceMetadata) {
            return new ResourceMetadata(
                $annotation->shortName,
                $annotation->description ?? $this->defaults['description'] ?? null,
                $annotation->iri ?? $this->defaults['iri'] ?? null,
                // $annotation->itemOperations ?? $this->defaults['item_operations'] ?? null,
                // $annotation->collectionOperations ?? $this->defaults['collection_operations'] ?? null,
                $attributes,
                // $annotation->subresourceOperations,
                $annotation->graphql ?? $this->defaults['graphql'] ?? null
            );
        }

    }

