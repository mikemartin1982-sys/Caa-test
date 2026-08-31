package com.caa.platform.equipment;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import org.springframework.stereotype.Service;
import org.springframework.web.multipart.MultipartFile;

import java.io.IOException;
import java.io.InputStream;
import java.math.BigDecimal;
import java.util.ArrayList;
import java.util.List;
import java.util.zip.ZipEntry;
import java.util.zip.ZipInputStream;

/**
 * Michael, 2026-08-31 -- Truck/Trailer Equipment feature. Parses a real
 * Chart Recorder 5-Filter export zip (confirmed against a real sample,
 * "2025-08-25-J-trailer -T-208.zip") -- reads what's already there,
 * never computes or judges anything itself. Confirmed with Michael:
 * a real, second staff member already reviews and signs off on a
 * 5-Filter before it's considered valid -- this service is purely the
 * "read the file accurately" half of that; the six named EPA
 * parameters are never inferred here, only ever supplied by that
 * reviewer afterward, in the actual submit step
 * (TestingSystemController.submitCalibrationRecord()).
 *
 * Only Calibration-ffc.json is read -- the zip's other four JSON files
 * (range-tests, linearity-test, response-time, drift-test) hold the
 * same underlying data at a much more granular, raw-measurement level;
 * Calibration-ffc.json's own top-level summary arrays ("Low/Medium/
 * High Filter Test Results", 5 values each) are the real, final
 * per-cycle values -- confirmed as the right source, not the raw
 * sub-measurements.
 */
@Service
public class ChartRecorderImportService {

    private final ObjectMapper objectMapper = new ObjectMapper();

    public record ParsedTestingSystemInfo(String trailerName, String lightSource, String photoCellId,
                                            String opAmpCardId, String monitorId, String dataSourceId) {}

    /**
     * Michael, 2026-08-31 -- deliberately NOT matched to a specific
     * CalibrationPane here -- CalibrationPane has no Low/Medium/High
     * designation of its own, just an identifier and a certified
     * value, and auto-matching risks a silent, wrong match. Confirmed
     * with Michael's own explanation of the "Category Error" rounding
     * quirk: getting a pane match wrong would mean the tolerance check
     * runs against the wrong reference value entirely. The reviewer
     * confirms which real CalibrationPane is Low/Medium/High
     * themselves, at submit time.
     */
    public record ParsedPaneReadings(List<BigDecimal> low, List<BigDecimal> medium, List<BigDecimal> high) {}

    public record ParsedRawTestValues(List<BigDecimal> responseTimeSeconds, BigDecimal voltsToLightSource,
                                        BigDecimal voltsToSmokeGenerator) {}

    public record ParsedCalibrationImport(ParsedTestingSystemInfo systemInfo, ParsedPaneReadings paneReadings,
                                            ParsedRawTestValues rawValues, String operatorName, String reviewerName,
                                            String dateOfCalibration) {}

    public ParsedCalibrationImport parse(MultipartFile zipFile) throws IOException {
        JsonNode body = findAndParseCalibrationJson(zipFile);

        ParsedTestingSystemInfo systemInfo = new ParsedTestingSystemInfo(
                text(body, "TrailerName"),
                text(body, "LightSource"),
                text(body, "PhotocellId"),
                text(body, "photocellCardNumber"),
                text(body, "MonitorId"),
                text(body, "DataSourceId"));

        ParsedPaneReadings paneReadings = new ParsedPaneReadings(
                decimalArray(body, "Low Filter Test Results"),
                decimalArray(body, "Medium Filter Test Results"),
                decimalArray(body, "High Filter Test Results"));

        ParsedRawTestValues rawValues = new ParsedRawTestValues(
                decimalArray(body, "Response Time Test Results"),
                decimal(body, "VoltsToLightSource"),
                decimal(body, "VoltsToSmokeGenerator"));

        return new ParsedCalibrationImport(systemInfo, paneReadings, rawValues,
                text(body, "OperatorName"), text(body, "reviewer"), text(body, "DateOfCalibration"));
    }

    /**
     * Michael, 2026-08-31 -- matched by filename suffix, not a
     * hardcoded full path -- the real zip's internal folder name
     * includes the trailer/session identifier itself
     * ("FiveFilterCalibrations/2025-08-25-J-trailer -T-208/..."),
     * which varies per upload.
     */
    private JsonNode findAndParseCalibrationJson(MultipartFile zipFile) throws IOException {
        try (ZipInputStream zip = new ZipInputStream(zipFile.getInputStream())) {
            ZipEntry entry;
            while ((entry = zip.getNextEntry()) != null) {
                if (entry.getName().endsWith("Calibration-ffc.json")) {
                    JsonNode root = objectMapper.readTree(readAllBytes(zip));
                    JsonNode documentBody = root.get("DocumentBody");
                    if (documentBody == null) {
                        throw new IllegalArgumentException("Calibration-ffc.json is missing its DocumentBody -- not a recognized Chart Recorder export.");
                    }
                    return documentBody;
                }
            }
        }
        throw new IllegalArgumentException("This zip doesn't contain a Calibration-ffc.json file -- not a recognized Chart Recorder 5-Filter export.");
    }

    private byte[] readAllBytes(InputStream in) throws IOException {
        return in.readAllBytes();
    }

    private String text(JsonNode body, String field) {
        JsonNode node = body.get(field);
        return node != null && !node.isNull() ? node.asText().trim() : null;
    }

    private BigDecimal decimal(JsonNode body, String field) {
        JsonNode node = body.get(field);
        return node != null && !node.isNull() ? node.decimalValue() : null;
    }

    private List<BigDecimal> decimalArray(JsonNode body, String field) {
        List<BigDecimal> result = new ArrayList<>();
        JsonNode arr = body.get(field);
        if (arr != null && arr.isArray()) {
            arr.forEach(v -> result.add(v.decimalValue()));
        }
        return result;
    }
}
