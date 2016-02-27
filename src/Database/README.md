## Rain Database

The October Rain Foundation is an extension of the Eloquent ORM used by Laravel. It adds the following features:

### Usage Instructions

See the [Illuminate Database instructions](https://github.com/illuminate/database/blob/master/README.md) for usage outside the Laravel framework.

### Alternate relations and events

Relations and events can be defined using an alternative syntax, which is preferred by the [October CMS platform](http://octobercms.com).

[See October CMS Model documentation](https://github.com/octobercms/docs/blob/master/database-model.md)

### Model validation

Models can define validation rules Laravel's built-in Validator class.

[See October CMS Model documentation](https://github.com/octobercms/docs/blob/master/database-model.md)

### Deferred bindings

Deferred bindings allow you to postpone model relationships until the master record commits the changes. This is particularly useful if you need to prepare some models (such as file uploads) and associate them to another model that doesn't exist yet.

[See Deferred binding documentation](https://github.com/octobercms/docs/blob/master/database-deferred-binding.md)

### Tree Trait Interface

Traits do not support interfaces so this cannot be executed in the code. These are the expectations of a "Tree" trait, currently: NestedTree, SimpleTree.

These methods should support query builder chaining, i.e defined as scopes:

- getAllRoot(): Return just the root nodes.
- getNested(): Return all nodes with the `children` relationship eager loaded.
- listsNested(): Returns a key, value array of records, where values are indented based on their level.

These methods do not require chaining:

- getChildren(): Return the child nodes below this one.
- getChildCount(): Return the number of children below this node.

All models must return a collection of the base class `October\Rain\Database\TreeCollection`.
