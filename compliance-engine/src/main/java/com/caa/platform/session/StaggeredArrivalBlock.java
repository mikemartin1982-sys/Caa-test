package com.caa.platform.session;
 
import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;
 
import java.time.LocalTime;
 
/**
 * Section 4: Texas-only. The proctor threshold (24, corrected by
 * Michael, 2026-08-19 real-world research -- previously documented
 * here as 26) is evaluated PER BLOCK, not total session enrollment --
 * a block at or under 24 satisfies the requirement on its own, so
 * staggering is a genuine compliance mechanism, not just a logistics
 * convenience.
 */
@Entity
@Table(name = "staggered_arrival_blocks")
@Getter
@Setter
@NoArgsConstructor
public class StaggeredArrivalBlock {
 
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;
 
    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "session_day_id", nullable = false)
    private SessionDay sessionDay;
 
    @Column(name = "block_number", nullable = false)
    private Integer blockNumber;
 
    @Column(name = "start_time", nullable = false)
    private LocalTime startTime;
 
    @Column(name = "end_time", nullable = false)
    private LocalTime endTime;
}