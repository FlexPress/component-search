# FlexPress search component

### AbstractSearch

## Implement a concreate class with the following methods"
- getSearchablePostTypes() - Used to return an arrya of post types you want to search
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
