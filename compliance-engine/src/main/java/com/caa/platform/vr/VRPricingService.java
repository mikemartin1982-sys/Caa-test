package com.caa.platform.vr;

import com.caa.platform.client.Client;
import org.springframework.stereotype.Service;

import java.math.BigDecimal;

/**
 * Section 3a/4b: $275 standard, $250 at 50+ tokens, $225 at 100+ tokens --
 * evaluated PER TRANSACTION, not cumulative across purchase history
 * (deliberate, since client headcounts churn). A client's VR pricing
 * override (custom rate or No-Cost/$0), when set, takes precedence over
 * the standard tiers entirely (Section 4b).
 */
@Service
public class VRPricingService {

    private static final BigDecimal STANDARD_RATE = new BigDecimal("275.00");
    private static final BigDecimal TIER_50_RATE = new BigDecimal("250.00");
    private static final BigDecimal TIER_100_RATE = new BigDecimal("225.00");

    public BigDecimal ratePerToken(Client client, int tokenQuantity) {
        if (client.isVrPricingNoCost()) {
            return BigDecimal.ZERO;
        }
        if (client.getVrPricingOverrideRate() != null) {
            return client.getVrPricingOverrideRate();
        }
        if (tokenQuantity >= 100) {
            return TIER_100_RATE;
        }
        if (tokenQuantity >= 50) {
            return TIER_50_RATE;
        }
        return STANDARD_RATE;
    }

    public BigDecimal totalPrice(Client client, int tokenQuantity) {
        return ratePerToken(client, tokenQuantity).multiply(BigDecimal.valueOf(tokenQuantity));
    }
}