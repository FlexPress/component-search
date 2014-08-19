# FlexPress search component

### AbstractSearch

## Implement a concreate class with the following methods"
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
