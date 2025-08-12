<?php

namespace XRPLSale\Services;

use XRPLSale\XRPLSaleClient;
use XRPLSale\Models\Investment;
use XRPLSale\Models\PaginatedResponse;

/**
 * Investments Service
 * 
 * Manage and track investments on the XRPL.Sale platform
 */
class InvestmentsService
{
    private XRPLSaleClient $client;
    
    public function __construct(XRPLSaleClient $client)
    {
        $this->client = $client;
    }
    
    /**
     * Create a new investment
     * 
     * @param array $investmentData Investment data
     * @return Investment
     */
    public function create(array $investmentData): Investment
    {
        $response = $this->client->post('/investments', $investmentData);
        return new Investment($response);
    }
    
    /**
     * Get investment by ID
     * 
     * @param string $investmentId Investment ID
     * @return Investment
     */
    public function get(string $investmentId): Investment
    {
        $response = $this->client->get("/investments/{$investmentId}");
        return new Investment($response);
    }
    
    /**
     * List all investments with optional filters
     * 
     * @param array $options Filter and pagination options
     * @return PaginatedResponse
     */
    public function list(array $options = []): PaginatedResponse
    {
        $response = $this->client->get('/investments', $options);
        return new PaginatedResponse($response, Investment::class);
    }
    
    /**
     * Get investments by project
     * 
     * @param string $projectId Project ID
     * @param array $options Pagination options
     * @return PaginatedResponse
     */
    public function getByProject(string $projectId, array $options = []): PaginatedResponse
    {
        $response = $this->client->get("/projects/{$projectId}/investments", $options);
        return new PaginatedResponse($response, Investment::class);
    }
    
    /**
     * Get investments by investor
     * 
     * @param string $investorAccount Investor wallet address
     * @param array $options Pagination options
     * @return PaginatedResponse
     */
    public function getByInvestor(string $investorAccount, array $options = []): PaginatedResponse
    {
        $options['investor_account'] = $investorAccount;
        $response = $this->client->get('/investments', $options);
        return new PaginatedResponse($response, Investment::class);
    }
    
    /**
     * Get investor summary
     * 
     * @param string $investorAccount Investor wallet address
     * @return array
     */
    public function getInvestorSummary(string $investorAccount): array
    {
        return $this->client->get("/investors/{$investorAccount}/summary");
    }
    
    /**
     * Get investor portfolio
     * 
     * @param string $investorAccount Investor wallet address
     * @return array
     */
    public function getInvestorPortfolio(string $investorAccount): array
    {
        return $this->client->get("/investors/{$investorAccount}/portfolio");
    }
    
    /**
     * Simulate an investment
     * 
     * @param array $simulationData Simulation parameters
     * @return array
     */
    public function simulate(array $simulationData): array
    {
        return $this->client->post('/investments/simulate', $simulationData);
    }
    
    /**
     * Calculate tier for investment amount
     * 
     * @param string $projectId Project ID
     * @param string $amountXRP Amount in XRP
     * @return array
     */
    public function calculateTier(string $projectId, string $amountXRP): array
    {
        return $this->client->post('/investments/calculate-tier', [
            'project_id' => $projectId,
            'amount_xrp' => $amountXRP,
        ]);
    }
    
    /**
     * Cancel an investment (if allowed)
     * 
     * @param string $investmentId Investment ID
     * @return Investment
     */
    public function cancel(string $investmentId): Investment
    {
        $response = $this->client->post("/investments/{$investmentId}/cancel");
        return new Investment($response);
    }
    
    /**
     * Claim tokens for an investment
     * 
     * @param string $investmentId Investment ID
     * @return array
     */
    public function claimTokens(string $investmentId): array
    {
        return $this->client->post("/investments/{$investmentId}/claim");
    }
    
    /**
     * Get investment statistics
     * 
     * @param string $investmentId Investment ID
     * @return array
     */
    public function getStats(string $investmentId): array
    {
        return $this->client->get("/investments/{$investmentId}/stats");
    }
    
    /**
     * Get investment history for an investor
     * 
     * @param string $investorAccount Investor wallet address
     * @param array $options Filter options
     * @return PaginatedResponse
     */
    public function getHistory(string $investorAccount, array $options = []): PaginatedResponse
    {
        $response = $this->client->get("/investors/{$investorAccount}/history", $options);
        return new PaginatedResponse($response, Investment::class);
    }
    
    /**
     * Export investment data
     * 
     * @param array $exportOptions Export parameters
     * @return array
     */
    public function export(array $exportOptions): array
    {
        return $this->client->post('/investments/export', $exportOptions);
    }
}