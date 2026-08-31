package com.caa.platform.client;

import org.springframework.stereotype.Service;

import java.util.List;
import java.util.regex.Pattern;

/**
 * Section 3c: derives Client.recordName and Client.portalDisplayName from
 * the raw Company field.
 *
 * Resolved design (Section 3c "Resolved" note): strip via a MAINTAINED
 * SUFFIX LIST, not blind comma-splitting -- a naive "cut after the last
 * comma" rule risks misfiring on a company name that legitimately contains
 * a comma for another reason. Official documentation (Brevo campaigns, the
 * website's first mention) correctly keeps the full ", Inc" per legal
 * convention -- this derivation is a THIRD, consistent, report-safe form,
 * not a replacement for those other uses.
 *
 * Example: "Compliance Assurance Associates, Inc" + city "Harvest" + state
 * "AL" -> recordName "Compliance Assurance Associates - Harvest, AL",
 * portalDisplayName "Compliance Assurance Associates".
 */
@Service
public class ClientNameDerivationService {

    // Maintained list of known legal suffixes to strip. Extend as needed --
    // deliberately NOT a blind "everything after the last comma" rule.
    private static final List<String> LEGAL_SUFFIXES = List.of(
            "Inc.", "Inc", "LLC", "L.L.C.", "Corp.", "Corp", "Co.", "Ltd.", "Ltd"
    );

    private static final Pattern TRAILING_COMMA_SUFFIX = buildSuffixPattern();

    private static Pattern buildSuffixPattern() {
        // Matches ", <suffix>" at the end of the string, for any suffix in the list.
        String alternation = String.join("|", LEGAL_SUFFIXES.stream()
                .map(Pattern::quote)
                .toList());
        return Pattern.compile(",?\\s*(" + alternation + ")\\s*$", Pattern.CASE_INSENSITIVE);
    }

    /** Strips a known legal suffix from the company name, if present. */
    public String stripLegalSuffix(String company) {
        if (company == null) {
            return null;
        }
        return TRAILING_COMMA_SUFFIX.matcher(company.trim()).replaceAll("").trim();
    }

    /** Portal Display Name: just the company name, suffix stripped (Section 3c). */
    public String derivePortalDisplayName(String company) {
        return stripLegalSuffix(company);
    }

    /**
     * Record Name: "{Company, suffix stripped} - {City}, {State}" -- used
     * in staff-facing/admin contexts to disambiguate multiple locations of
     * the same company (Section 3c). Falls back to just the stripped
     * company name if city/state aren't set yet.
     */
    public String deriveRecordName(String company, String city, String state) {
        String stripped = stripLegalSuffix(company);
        if (city == null || city.isBlank() || state == null || state.isBlank()) {
            return stripped;
        }
        return stripped + " - " + city + ", " + state;
    }
}
