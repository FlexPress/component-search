# FlexPress search component

## Install with Pimple
- This example creates a search manager with a simple text query builder.
```
$pimple["textQueryBuilder"] = function () {
  return new TextQueryBuilder();
};

$pimple['searchManager'] = function($c) {
  return new Search($c["DatabaseAdapter"], $c["Queue"], $c["Request"], array(
    $c["textQueryBuilder"]
  ));
};
```

## Implement a concreate class with the following methods
- getSearchablePostTypes() - Used to return an array of post types you want to search
- outputResults() - Used to output the results, you can use wordpress loop functions you are use to but prefix them with $this-> and convert them to camelcase. For example have_posts becomes $this->havePosts()

### Very simple example
```
class Search extends AbstractSearch {

    protected function getSearchablePostTypes()
    {
        return array('post', 'page');
    }

    public function outputResults()
    {
        while($this->havePosts()) {

            $post = $this->thePost();
            echo $post->post_title . "<br/>";

        }
    }

    public function outputFilters()
    {

        // this should be done in a view and build your own FilterHelper
        echo '<form><input type="text" name="<?php echo $this->getUniqueQueryKey(TextQueryBuilder::QUERY_VAR_KEYWORDS); ?>"/>';
        echo '<input type="submit" value="Search"/></form>';
        echo '<br/>';

    }

}
```
- This example uses the TextQueryBuilders query var keywords as the input, then combined with the use of the querybuilder itself process the text value entered.
- Please note as mentioned in the comments, you should be using Timber/Twig to output your markup and not simply echo it.

## Using the search manager

```
$searchManager = $pimple["searchManager"];
$searchManager->processSearch();

$searchManager->outputResults();
```

## More advanced usage
- The search component currently comes in various flavours, the standard search AbstractSearch or a paginated search call AbstractPaginatedSearch; both of which can utilise the SummarisedSearchTrait, which simple standardises the output of a search summary.
- At the core of the search manager are the query builders, you inject these into the consutrctor as seen above with the TextQueryBuilder example.
- Along with the builtin QueryBuilders you can also create your own, all you have to do is implement the QueryBuilderInterface.
- The list of builtin QueryBuilders are Date, Meta, Taxonomy and Text.

## Creating your own QueryBuilder

- Implement the QueryBuilderInterface interface, which means implementing methods for updateQuery and getQueryFields.
- The updateQuery is where you are passed useful variables to aid you in the extending of the query, you get the search manager that you are attached to, the sql array and a database adapter(wpdb)
- Knowing all this we will now create a simple QueryBuilder that lets us exclude certain pages, we will presume that we are being sent through an array of post ids.

```
class PageExcluderQueryBuilder implements QueryBuilderInterface
{

    const QUERY_VAR_EXCLUDED_POST_IDS = 'excluded-post-ids';

    public function updateQuery(AbstractSearch $searchManager, array $sql, \wpdb $databaseAdapter)
    {

        // dont process anything if we dont have the query var we need
        if (!$searchManager->queryVarExists(self::QUERY_VAR_EXCLUDED_POST_IDS)) {
            return $sql;
        }

        $postIds = $searchManager->getQueryVar(self::QUERY_VAR_EXCLUDED_POST_IDS);

        if (empty($postIds)) {
            return $sql;
        }
        
        foreach($postIds as $postId){
            $sql["where"] .=  $databaseAdapter->prepare("and p.ID <> %d ", $postId);
        }

        return $sql;
    }

    public function getQueryFields()
    {
        return array(self::QUERY_VAR_EXCLUDED_POST_IDS);
    }
}

```
- We now need to add this to the pimple config
```
$pimple["textQueryBuilder"] = function () {
  return new TextQueryBuilder();
};

// Add this config and inject any dependancies we need
$pimple["pageExcluderQueryBuilder"] = function () {
  return new PageExcluderQueryBuilder();
};

$pimple['searchManager'] = function($c) {
  return new Search($c["DatabaseAdapter"], $c["Queue"], $c["Request"], array(
    $c["textQueryBuilder"],
    $c["pageExcluderQueryBuilder"] // add the config into here and it will be utilised by the search manager
  ));
};
```
- You should now know how to add a query builder, all that was missing from this example is the code used to input the excluded post types, you could do something like this:
```
<select multiple name="<?php echo $this->getUniqueQueryKey(PageExcluderQueryBuilder::QUERY_VAR_EXCLUDED_POST_IDS); ?>">
    <option value="23">Some page</option>
    <option value="26">Some other page</option>
</select>
```
## Filter Helpers
- Filter helpers are a tad more difficult than the QueryBuilders as you need to hook them up to the related QueryBuilder.
- FilterHelpers allow you to create a reusable helper to output filters.
- In this example we will go over the meta filter helper
```

$this->metaFilterHelper->setSearchableKeys(array("fp_page_layout"));
$this->metaFilterHelper->outputMetaSearch(
  function ($metaKey, $metaValues, $formattedMetaKey) {
  
    echo '<label for="', $metaKey, '">', $formattedMetaKey, '</label>';
    echo '<select name="', $this->getUniqueQueryKey(MetaQueryBuilder::FILTER_VAR_META), '[', $metaKey, ']">';
    
    foreach ($metaValues as $metaValue) {
      echo '<option>', $metaValue, '</option>';
    }
    
    echo '</select>';
    
  }
);
```
- Lets run through this example, first you will notice that we are accessing the metaHelper property on ourself, you would inject this as a dependacy using pimple like this:
```
$this["metaFilterHelper"] = function () {
    return new MetaFilterHelper();
};

$this["Search"] = function ($c) {
    return new Search($c["DatabaseAdapter"], $c["Queue"], $c["Request"], array(
        $c["TextQueryBuilder"]
    ),
    $c["metaFilterHelper"] // you would add it like this or you could use an array like above if you had multiple ones, this is left up to you.
    );
};
```
- You would also have to change your constructor on your concreate class:
```
public function __construct(
    \wpdb $databaseAdapter,
    \SplQueue $queryBuilders,
    Request $request,
    array $queryBuildersArray,
    MetaFilterHelper $metaFilterHelper // add it in here
) {
    parent::__construct(
        $databaseAdapter,
        $queryBuilders,
        $request,
        $queryBuildersArray
    );

    // assign it
    $this->metaFilterHelper = $metaFilterHelper;

}
```
- Next you will notice that we calling setSearchableKeys:
```
$this->metaFilterHelper->setSearchableKeys(array("fp_page_layout"));
```
- Here you are specifying what keys you want to be passed back to the callable that you will implement next, this allows you to write one method to output the filter for a given standard input. This means you can create a standard select box or something to show the list of meta options and when you want to add another searchable meta option you can just add it to the array and as you have already implemented the code to output it you are all done, saving a lot of time and making the filter very flexible.
- So now we should look at what the callable is doing:
```
$this->metaFilterHelper->outputMetaSearch(
  function ($metaKey, $metaValues, $formattedMetaKey) {
  
    echo '<label for="', $metaKey, '">', $formattedMetaKey, '</label>';
    echo '<select name="', $this->getUniqueQueryKey(MetaQueryBuilder::FILTER_VAR_META), '[', $metaKey, ']">';
    
    foreach ($metaValues as $metaValue) {
      echo '<option>', $metaValue, '</option>';
    }
    
    echo '</select>';
    
  }
);
```
- You call the outputMetaSearch method and supply a function that takes the params, $metaKey, $metaValues and $formattedMetaKey, of which are fairly self explanatory, the $formattedMetaKey just makes the meta key readable so my_meta_key becomes My Meta Key, which you can use for your label.
- Inside the method you then output a single field, as this method is called for each meta key you speficifed previously (setSearchableKeys).
```
    echo '<select name="', $this->getUniqueQueryKey(MetaQueryBuilder::FILTER_VAR_META), '[', $metaKey, ']">';
```
- This is the important line, what it is doing is getting you a unique key for the given search class, which include the namespace, this allows you to have multiple searches on a single page.
- We then have to hook this up to a QueryBuilder so we grab the constant for FILTER_VAR_XXX from MetaQueryBuilder, so now we have the correct key but we need to format it correctly, for the meta query builder it expects an array of key => values, so we use [] to notable it in an array and place the $metaKey inside of them and we are all done.
- If you now place the code into your outputFilters() method and call it whereever you want to display your filters you are all done.

## Public methods
- processSearch

## Protected methods
- init 
- setupQueryVars
- getUniqueQueryKey
- 
