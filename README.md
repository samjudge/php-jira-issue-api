# php-jira-issue-api
A simple to use PHP interface for JIRA's restful API.

Include `Project.php` in your project, and create an instance of the `Project` class, passing a host, project key, username  and password.

`$cfms_on` may be turned off in your project, doing so will cause custom fields to NOT be mapped to their readable names, instead reverting to their generic `customfield_xxxxxx` identifier. These custom field maps are turned on by default.

Pass issue $data as an array, in the form of
```
project->create_issue(array(
  "summary"=>"At minimum, I must be set in order to create an issue"
));
```
In order to create multiple issues
```
project->create_multiple_issues(array(
    array(
      "summary"=>"One"
    ),
    array(
      "summary"=>"two"
    )
));
```
You may pass the optional `$is_atomic` variable in order to make this multiple issue creation atomic (all-or-nothing). It is atomic by default.

`query($jql = false, $result_limit = -1, $fields = array())`

Passing the `$fields` argument will limit the fields returned by the query. A `$result_limit` > `0` will limit the results to the first `$result_limit` issues. JQL is used for queries.

Using the `query` method will return an Issue_Collection. An Issue_Collection represents many issues.

`get_fields` takes an array of strings identifying the names of the fields you want, and returns an array arrays, representing every issue's value for that field in the issue_collection.

You pass an array as the same form used by `create_issue` to `set_fields` in order to set the values of these issues. The issues are updated both locally and remotely on JIRA.

:)
