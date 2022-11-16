PHP Entity to TypeScript
========================

Tool for simple conversion of PHP entities to typescript. No dependencies added.

- No another Composer package dependencies
- Native support for PHP property types and annotations
- Analyze dependent entities recursively including arrays
- Native support for TypeScript Record like `Record<number, Record<boolean, Record<string, number>>>;`
- Unique interface names when scan multiple entities

How to use
----------

```php
// create service instance (no dependency needed!)
$service = new EntityToTypescript();

// analyze your service and write dependencies to bag
$bag = new DependencyBag();
$bag = $service->process(SampleEntity::class, $bag);

// render typescript to string
echo $service->render($bag);
```

The principle of storing dependencies in Badge
----------------------------------------------

When scanning a passed PHP entity, dependent entities are also recursively scanned, including annotations and compound types such as PHP arrays. Information about all scanned entities is stored in a common location in a so-called `Badge`, which ensures that the names of all generated interfaces are unique.

If you need to generate typescript definitions for a group of multiple entities, or an entire project, simply pass the same Badge instance to multiple generations.
