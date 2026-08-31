package com.caa.platform.equipment;

import org.springframework.data.jpa.repository.JpaRepository;

public interface TrailerRepository extends JpaRepository<Trailer, Long> {

    /** Michael, 2026-08-31 -- Truck/Trailer Equipment feature. Same reasoning as TruckRepository.existsByIdentifier(). */
    boolean existsByIdentifier(String identifier);
    /** Michael, 2026-08-31 -- same reasoning as TruckRepository.existsByIdentifierAndIdNot(). */
    boolean existsByIdentifierAndIdNot(String identifier, Long id);
}
