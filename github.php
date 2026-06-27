<?php
require_once 'config.php';

function githubGraphQL($query, $variables = [])
{
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.github.com/graphql',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . GITHUB_TOKEN,
            'User-Agent: FalconValleyBot',
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'query' => $query,
            'variables' => $variables
        ])
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        file_put_contents(
            'github-error.log',
            curl_error($ch) . PHP_EOL,
            FILE_APPEND
        );
    }

    curl_close($ch);

    return json_decode($response, true);
}

function githubRequest($method, $url)
{
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => [
            'Accept: application/vnd.github+json',
            'Authorization: Bearer '.GITHUB_TOKEN,
            'User-Agent: FalconValleyBot'
        ]
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        file_put_contents('github-error.log', curl_error($ch).PHP_EOL, FILE_APPEND);
    }

    curl_close($ch);

    return json_decode($response, true);
}

function githubUser($username)
{
    return githubRequest('GET', "https://api.github.com/users/$username");
}

function getIssueNodeId($issueNumber)
{
    $query = '
    query($owner:String!, $repo:String!, $number:Int!) {
      repository(owner:$owner, name:$repo) {
        issue(number:$number) {
          id
          title
        }
      }
    }';

    return githubGraphQL(
        $query,
        [
            'owner' => GITHUB_OWNER,
            'repo' => GITHUB_REPO,
            'number' => (int)$issueNumber
        ]
    );
}


function getProjects()
{
    $query = '
    query {
      viewer {
        projectsV2(first:20) {
          nodes {
            id
            title
          }
        }
      }
    }';

    return githubGraphQL($query);
}

function getProjectFields($projectId)
{
    $query = '
    query($projectId:ID!) {
      node(id:$projectId) {
        ... on ProjectV2 {

          title

          fields(first:50) {
            nodes {

              ... on ProjectV2SingleSelectField {
                id
                name

                options {
                  id
                  name
                }
              }

            }
          }
        }
      }
    }';

    return githubGraphQL(
        $query,
        [
            'projectId' => $projectId
        ]
    );
}
function getProjectItemId($projectId, $issueNodeId)
{
    $query = '
    query($projectId:ID!) {
      node(id:$projectId) {
        ... on ProjectV2 {
          items(first:100) {
            nodes {
              id
              content {
                ... on Issue {
                  id
                }
              }
            }
          }
        }
      }
    }';

    $result = githubGraphQL(
        $query,
        [
            'projectId' => $projectId
        ]
    );

    $items =
        $result['data']['node']['items']['nodes']
        ?? [];

    foreach ($items as $item) {

        if (
            ($item['content']['id'] ?? null)
            ===
            $issueNodeId
        ) {
            return $item['id'];
        }
    }

    return null;
}

function updateProjectStatus(
    $projectId,
    $itemId,
    $fieldId,
    $optionId
)
{
    $query = '
    mutation(
        $projectId:ID!,
        $itemId:ID!,
        $fieldId:ID!,
        $optionId:String!
    ) {
      updateProjectV2ItemFieldValue(
        input:{
          projectId:$projectId
          itemId:$itemId
          fieldId:$fieldId
          value:{
            singleSelectOptionId:$optionId
          }
        }
      ) {
        projectV2Item {
          id
        }
      }
    }';

    return githubGraphQL(
        $query,
        [
            'projectId' => $projectId,
            'itemId' => $itemId,
            'fieldId' => $fieldId,
            'optionId' => $optionId
        ]
    );
}

function getIssueStatus(
    $projectId,
    $issueNodeId
)
{
    $query = '
    query($projectId:ID!) {
      node(id:$projectId) {

        ... on ProjectV2 {

          items(first:100) {

            nodes {

              content {
                ... on Issue {
                  id
                }
              }

              fieldValues(first:20) {

                nodes {

                  ... on ProjectV2ItemFieldSingleSelectValue {
                    name

                    field {
                      ... on ProjectV2FieldCommon {
                        name
                      }
                    }
                  }

                }
              }

            }
          }
        }
      }
    }';

    $result = githubGraphQL(
        $query,
        [
            'projectId' => $projectId
        ]
    );

    foreach (
        $result['data']['node']['items']['nodes']
        ?? []
        as $item
    ) {

        if (
            ($item['content']['id'] ?? null)
            !==
            $issueNodeId
        ) {
            continue;
        }

        foreach (
            $item['fieldValues']['nodes']
            as $field
        ) {

            if (
                ($field['field']['name'] ?? '')
                ===
                'Status'
            ) {

                return $field['name'];
            }
        }
    }

    return 'Unknown';
}

function getIssueDetails($issueNumber)
{
    $query = '
    query(
        $owner:String!,
        $repo:String!,
        $number:Int!
    ) {
      repository(
        owner:$owner,
        name:$repo
      ) {

        issue(
          number:$number
        ) {

          id
          number
          title
          body
          url

          assignees(first:10) {
            nodes {
              login
            }
          }

          labels(first:20) {
            nodes {
              name
            }
          }
        }
      }
    }';

    return githubGraphQL(
        $query,
        [
            'owner' => GITHUB_OWNER,
            'repo' => GITHUB_REPO,
            'number' => (int)$issueNumber
        ]
    );
}

function getMyProjectIssues($githubUser, $projectId)
{
    $query = '
    query($projectId:ID!) {
      node(id:$projectId) {
        ... on ProjectV2 {

          items(first:100) {

            nodes {

              id

              fieldValues(first:20) {
                nodes {

                  ... on ProjectV2ItemFieldSingleSelectValue {
                    name
                    field {
                      ... on ProjectV2FieldCommon {
                        name
                      }
                    }
                  }

                }
              }

              content {
                ... on Issue {
                  number
                  title

                  assignees(first:10) {
                    nodes {
                      login
                    }
                  }
                }
              }

            }
          }

        }
      }
    }';

    return githubGraphQL(
        $query,
        [
            'projectId' => $projectId
        ]
    );
}

function getAssignedIssues($githubUser)
{
    $q = urlencode(
        "is:issue assignee:$githubUser repo:" .
        GITHUB_OWNER . "/" .
        GITHUB_REPO .
        " is:open"
    );

    return githubRequest(
        'GET',
        "https://api.github.com/search/issues?q=$q"
    );
}




