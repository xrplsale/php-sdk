<?php

namespace XRPLSale\Models;

/**
 * Project Model
 * 
 * Represents a token sale project on the XRPL.Sale platform
 */
class Project extends BaseModel
{
    public string $id;
    public string $name;
    public string $description;
    public string $tokenSymbol;
    public string $tokenIssuer;
    public string $totalSupply;
    public string $status;
    public array $tiers = [];
    public ?string $saleStartDate;
    public ?string $saleEndDate;
    public ?string $websiteUrl;
    public ?string $whitepaperUrl;
    public ?string $logoUrl;
    public ?string $bannerUrl;
    public array $socialLinks = [];
    public array $teamMembers = [];
    public ?string $contractAddress;
    public ?string $escrowAddress;
    public bool $kycRequired;
    public bool $auditCompleted;
    public bool $globalFreezeEnabled;
    public array $metadata = [];
    public string $createdAt;
    public string $updatedAt;
    
    // Statistics
    public ?float $totalRaisedXRP;
    public ?int $totalInvestors;
    public ?int $currentTier;
    public ?float $currentPrice;
    public ?float $progressPercentage;
    public ?string $timeRemaining;
    
    /**
     * Check if project is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
    
    /**
     * Check if project is upcoming
     */
    public function isUpcoming(): bool
    {
        return $this->status === 'upcoming';
    }
    
    /**
     * Check if project is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
    
    /**
     * Check if project is paused
     */
    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }
    
    /**
     * Check if project is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
    
    /**
     * Get current tier information
     */
    public function getCurrentTier(): ?array
    {
        if ($this->currentTier !== null && isset($this->tiers[$this->currentTier - 1])) {
            return $this->tiers[$this->currentTier - 1];
        }
        return null;
    }
    
    /**
     * Get tier by number
     */
    public function getTier(int $tierNumber): ?array
    {
        return $this->tiers[$tierNumber - 1] ?? null;
    }
    
    /**
     * Calculate token amount for XRP investment
     */
    public function calculateTokenAmount(float $xrpAmount): float
    {
        $tier = $this->getCurrentTier();
        if (!$tier || !isset($tier['price_per_token'])) {
            return 0;
        }
        
        return $xrpAmount / (float) $tier['price_per_token'];
    }
    
    /**
     * Get remaining time as DateTime
     */
    public function getRemainingTime(): ?\DateTimeInterface
    {
        if (!$this->saleEndDate) {
            return null;
        }
        
        $endDate = new \DateTime($this->saleEndDate);
        $now = new \DateTime();
        
        if ($endDate <= $now) {
            return null;
        }
        
        return $endDate;
    }
    
    /**
     * Get formatted price
     */
    public function getFormattedPrice(): string
    {
        if ($this->currentPrice === null) {
            return 'N/A';
        }
        
        return number_format($this->currentPrice, 6) . ' XRP';
    }
    
    /**
     * Get formatted total raised
     */
    public function getFormattedTotalRaised(): string
    {
        if ($this->totalRaisedXRP === null) {
            return '0 XRP';
        }
        
        return number_format($this->totalRaisedXRP, 2) . ' XRP';
    }
    
    /**
     * Get social link by platform
     */
    public function getSocialLink(string $platform): ?string
    {
        return $this->socialLinks[$platform] ?? null;
    }
    
    /**
     * Check if KYC is required
     */
    public function requiresKYC(): bool
    {
        return $this->kycRequired;
    }
    
    /**
     * Check if audit is completed
     */
    public function hasAudit(): bool
    {
        return $this->auditCompleted;
    }
    
    /**
     * Check if global freeze is enabled
     */
    public function hasGlobalFreeze(): bool
    {
        return $this->globalFreezeEnabled;
    }
}