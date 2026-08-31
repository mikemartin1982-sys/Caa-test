package com.caa.platform.equipment;

import org.springframework.data.jpa.repository.JpaRepository;

public interface TruckRepository extends JpaRepository<Truck, Long> {

    /**
     * Michael, 2026-08-31 -- Truck/Trailer Equipment feature. A real,
     * targeted query, not findAll() + in-memory filter -- the exact
     * class of issue the performance audit caught elsewhere in this
     * project.
     */
    boolean existsByIdentifier(String identifier);
    /**
     * Michael, 2026-08-31 -- Truck/Trailer Equipment feature. Same
     * duplicate-identifier check as existsByIdentifier(), but excluding
     * the row itself -- needed for updates, where "this record keeping
     * its own current identifier" must never be mistaken for a
     * conflict with itself.
     */
    boolean existsByIdentifierAndIdNot(String identifier, Long id);
}
