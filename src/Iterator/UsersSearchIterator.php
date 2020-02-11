<?php
namespace Tustin\PlayStation\Iterator;

use Iterator;
use GuzzleHttp\Client;
use InvalidArgumentException;
use Tustin\PlayStation\Api\Model\User;
use Tustin\PlayStation\Filter\UserFilter;
use Tustin\PlayStation\Traits\Filterable;

class UsersSearchIterator extends AbstractApiIterator
{
    use Filterable;
    
    protected string $query;

    protected string $searchFields;
    
    public function __construct(Client $client, string $query, array $searchFields, int $limit)
    {
        if (empty($query))
        {
            throw new InvalidArgumentException('$query must contain a value.');
        }

        if (empty($searchFields))
        {
            throw new InvalidArgumentException('$searchFields must contain at least one value.');
        }

        if ($limit <= 0)
        {
            throw new InvalidArgumentException('$limit must be greater than zero.');
        }

        parent::__construct($client);
        $this->query = $query;
        $this->limit = $limit;
        $this->searchFields = implode(',', $searchFields);
        $this->access(0);
    }

    public function access($cursor) : void
    {
        $results = $this->get('https://friendfinder.api.np.km.playstation.net/friend-finder/api/v1/users/me/search', [
            'fields' => 'onlineId',
            'query' => $this->query,
            'searchTarget' => 'all',
            'searchFields' => $this->searchFields,
            'limit' => $this->limit,
            'offset' => $cursor,
            'rounded' => true
        ]);

        $this->update($results->totalResults, $results->searchResults);
    }

    /**
     * Gets users whose onlineId contains the specified string.
     *
     * @param string $text
     * @return Iterator
     */
    public function containing(string $text) : Iterator
    {
        yield from new UserFilter($this, $text);
    }

    /**
     * Gets the users who have PlayStation Plus.
     *
     * @return Iterator
     */
    public function hasPlus() : Iterator
    {
        return $this->where(function ($user) {
            return $user->hasPlus();
        });
    }

    public function current()
    {
        return new User(
            $this->httpClient,
            $this->getFromOffset($this->currentOffset)->onlineId,
            true
        );
    }
}