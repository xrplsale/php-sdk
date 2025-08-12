<?php

namespace XRPLSale\Services;

use XRPLSale\XRPLSaleClient;
use XRPLSale\Models\Project;
use XRPLSale\Models\PaginatedResponse;

/**
 * Projects Service
 * 
 * Manage token sale projects on the XRPL.Sale platform
 */
class ProjectsService
{
    private XRPLSaleClient $client;
    
    public function __construct(XRPLSaleClient $client)
    {
        $this->client = $client;
    }
    
    /**
     * List all projects with optional filters
     * 
     * @param array $options Filter and pagination options
     * @return PaginatedResponse
     */
    public function list(array $options = []): PaginatedResponse
    {
        $response = $this->client->get('/projects', $options);
        return new PaginatedResponse($response, Project::class);
    }
    
    /**
     * Get active projects
     * 
     * @param int $page Page number
     * @param int $limit Items per page
     * @return PaginatedResponse
     */
    public function getActive(int $page = 1, int $limit = 10): PaginatedResponse
    {
        return $this->list([
            'status' => 'active',
            'page' => $page,
            'limit' => $limit,
        ]);
    }
    
    /**
     * Get upcoming projects
     * 
     * @param int $page Page number
     * @param int $limit Items per page
     * @return PaginatedResponse
     */
    public function getUpcoming(int $page = 1, int $limit = 10): PaginatedResponse
    {
        return $this->list([
            'status' => 'upcoming',
            'page' => $page,
            'limit' => $limit,
        ]);
    }
    
    /**
     * Get completed projects
     * 
     * @param int $page Page number
     * @param int $limit Items per page
     * @return PaginatedResponse
     */
    public function getCompleted(int $page = 1, int $limit = 10): PaginatedResponse
    {
        return $this->list([
            'status' => 'completed',
            'page' => $page,
            'limit' => $limit,
        ]);
    }
    
    /**
     * Get a specific project by ID
     * 
     * @param string $projectId Project ID
     * @return Project
     */
    public function get(string $projectId): Project
    {
        $response = $this->client->get("/projects/{$projectId}");
        return new Project($response);
    }
    
    /**
     * Create a new project
     * 
     * @param array $projectData Project data
     * @return Project
     */
    public function create(array $projectData): Project
    {
        $response = $this->client->post('/projects', $projectData);
        return new Project($response);
    }
    
    /**
     * Update a project
     * 
     * @param string $projectId Project ID
     * @param array $updates Update data
     * @return Project
     */
    public function update(string $projectId, array $updates): Project
    {
        $response = $this->client->patch("/projects/{$projectId}", $updates);
        return new Project($response);
    }
    
    /**
     * Launch a project (make it active)
     * 
     * @param string $projectId Project ID
     * @return Project
     */
    public function launch(string $projectId): Project
    {
        $response = $this->client->post("/projects/{$projectId}/launch");
        return new Project($response);
    }
    
    /**
     * Pause a project
     * 
     * @param string $projectId Project ID
     * @return Project
     */
    public function pause(string $projectId): Project
    {
        $response = $this->client->post("/projects/{$projectId}/pause");
        return new Project($response);
    }
    
    /**
     * Resume a paused project
     * 
     * @param string $projectId Project ID
     * @return Project
     */
    public function resume(string $projectId): Project
    {
        $response = $this->client->post("/projects/{$projectId}/resume");
        return new Project($response);
    }
    
    /**
     * Cancel a project
     * 
     * @param string $projectId Project ID
     * @return Project
     */
    public function cancel(string $projectId): Project
    {
        $response = $this->client->post("/projects/{$projectId}/cancel");
        return new Project($response);
    }
    
    /**
     * Get project statistics
     * 
     * @param string $projectId Project ID
     * @return array
     */
    public function getStats(string $projectId): array
    {
        return $this->client->get("/projects/{$projectId}/stats");
    }
    
    /**
     * Get project investors
     * 
     * @param string $projectId Project ID
     * @param array $options Pagination options
     * @return PaginatedResponse
     */
    public function getInvestors(string $projectId, array $options = []): PaginatedResponse
    {
        $response = $this->client->get("/projects/{$projectId}/investors", $options);
        return new PaginatedResponse($response);
    }
    
    /**
     * Get project tiers
     * 
     * @param string $projectId Project ID
     * @return array
     */
    public function getTiers(string $projectId): array
    {
        return $this->client->get("/projects/{$projectId}/tiers");
    }
    
    /**
     * Update project tiers
     * 
     * @param string $projectId Project ID
     * @param array $tiers Tier data
     * @return array
     */
    public function updateTiers(string $projectId, array $tiers): array
    {
        return $this->client->put("/projects/{$projectId}/tiers", ['tiers' => $tiers]);
    }
    
    /**
     * Search projects
     * 
     * @param string $query Search query
     * @param array $options Additional search options
     * @return PaginatedResponse
     */
    public function search(string $query, array $options = []): PaginatedResponse
    {
        $options['q'] = $query;
        $response = $this->client->get('/projects/search', $options);
        return new PaginatedResponse($response, Project::class);
    }
    
    /**
     * Get featured projects
     * 
     * @param int $limit Number of projects to return
     * @return array
     */
    public function getFeatured(int $limit = 5): array
    {
        $response = $this->client->get('/projects/featured', ['limit' => $limit]);
        return array_map(fn($data) => new Project($data), $response['data'] ?? []);
    }
    
    /**
     * Get trending projects
     * 
     * @param string $period Time period (24h, 7d, 30d)
     * @param int $limit Number of projects to return
     * @return array
     */
    public function getTrending(string $period = '24h', int $limit = 10): array
    {
        $response = $this->client->get('/projects/trending', [
            'period' => $period,
            'limit' => $limit,
        ]);
        return array_map(fn($data) => new Project($data), $response['data'] ?? []);
    }
}