package com.caa.platform.vr;

import com.caa.platform.client.Client;
import com.caa.platform.client.ClientRepository;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.math.BigDecimal;

/**
 * Backs /vr/token-blocks in api-contract/openapi.yaml. Priced
 * per-transaction (Section 3a/4b) via VRPricingService, which honors a
 * client's VR pricing override (custom rate or No-Cost) ahead of the
 * standard $275/$250/$225 tiers.
 */
@RestController
@RequestMapping("/api/v1/vr/token-blocks")
public class VRController {

    private final VRTokenBlockRepository tokenBlockRepository;
    private final ClientRepository clientRepository;
    private final VRPricingService pricingService;

    public VRController(VRTokenBlockRepository tokenBlockRepository,
                         ClientRepository clientRepository,
                         VRPricingService pricingService) {
        this.tokenBlockRepository = tokenBlockRepository;
        this.clientRepository = clientRepository;
        this.pricingService = pricingService;
    }

    public record PurchaseRequest(Long clientId, Integer tokensPurchased) {}

    @PostMapping
    public ResponseEntity<VRTokenBlock> purchase(@RequestBody PurchaseRequest req) {
        Client client = clientRepository.findById(req.clientId())
                .orElseThrow(() -> new IllegalArgumentException("Client not found: " + req.clientId()));

        BigDecimal rate = pricingService.ratePerToken(client, req.tokensPurchased());

        VRTokenBlock block = new VRTokenBlock();
        block.setClient(client);
        block.setTokensPurchased(req.tokensPurchased());
        block.setTokensRemaining(req.tokensPurchased());
        block.setPricePaidPerToken(rate);

        return ResponseEntity.status(HttpStatus.CREATED).body(tokenBlockRepository.save(block));
    }
}
