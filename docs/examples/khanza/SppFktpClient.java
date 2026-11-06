package bridging.pdf;

import java.io.BufferedInputStream;
import java.awt.Desktop;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URISyntaxException;
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.StandardCopyOption;
import java.util.Locale;

/** Client Java 8+ untuk endpoint PDF sppFKTP. Tidak membutuhkan library tambahan. */
public final class SppFktpClient {
    private SppFktpClient() {
    }

    /** Membuka endpoint GET langsung di browser, tanpa unduh file lewat Java. */
    public static void openInBrowser(URL endpoint, String noRawat) throws IOException, URISyntaxException {
        if (noRawat == null || noRawat.trim().isEmpty() || noRawat.trim().length() > 17) {
            throw new IllegalArgumentException("no_rawat wajib diisi, maksimal 17 karakter.");
        }
        if (!Desktop.isDesktopSupported() || !Desktop.getDesktop().isSupported(Desktop.Action.BROWSE)) {
            throw new IOException("Komputer ini tidak mendukung pembukaan browser otomatis.");
        }
        String separator = endpoint.toExternalForm().contains("?") ? "&" : "?";
        URL preview = new URL(endpoint.toExternalForm() + separator + "no_rawat="
            + URLEncoder.encode(noRawat.trim(), "UTF-8"));
        Desktop.getDesktop().browse(preview.toURI());
    }

    public static Path download(URL endpoint, String noRawat, String integrationToken) throws IOException {
        if (noRawat == null || noRawat.trim().isEmpty() || noRawat.trim().length() > 17) {
            throw new IllegalArgumentException("no_rawat wajib diisi, maksimal 17 karakter.");
        }
        if (integrationToken == null || integrationToken.trim().isEmpty()) {
            throw new IllegalArgumentException("Token integrasi belum diisi.");
        }

        HttpURLConnection connection = (HttpURLConnection) endpoint.openConnection();
        Path pdf = null;
        boolean completed = false;
        try {
            connection.setRequestMethod("POST");
            connection.setConnectTimeout(15000);
            connection.setReadTimeout(60000);
            connection.setInstanceFollowRedirects(false);
            connection.setDoOutput(true);
            connection.setRequestProperty("X-Integration-Token", integrationToken);
            connection.setRequestProperty("Accept", "application/pdf");
            connection.setRequestProperty("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");

            byte[] body = ("no_rawat=" + URLEncoder.encode(noRawat.trim(), "UTF-8"))
                .getBytes(StandardCharsets.UTF_8);
            connection.setFixedLengthStreamingMode(body.length);
            try (OutputStream output = connection.getOutputStream()) {
                output.write(body);
            }

            int status = connection.getResponseCode();
            if (status != HttpURLConnection.HTTP_OK) {
                String message = "Gagal membuat sppFKTP (HTTP " + status + ").";
                try (InputStream error = connection.getErrorStream()) {
                    if (error != null) {
                        byte[] buffer = new byte[2048];
                        int count = error.read(buffer);
                        if (count > 0) {
                            message += " " + new String(buffer, 0, count, StandardCharsets.UTF_8);
                        }
                    }
                }
                throw new IOException(message);
            }

            String contentType = connection.getContentType();
            if (contentType == null || !contentType.toLowerCase(Locale.ROOT).split(";", 2)[0].trim().equals("application/pdf")) {
                throw new IOException("Respons server bukan PDF. Periksa URL endpoint sppFKTP.");
            }

            try (BufferedInputStream input = new BufferedInputStream(connection.getInputStream())) {
                input.mark(5);
                byte[] signature = new byte[5];
                int length = 0;
                while (length < signature.length) {
                    int count = input.read(signature, length, signature.length - length);
                    if (count == -1) {
                        break;
                    }
                    length += count;
                }
                if (length != 5 || !"%PDF-".equals(new String(signature, StandardCharsets.US_ASCII))) {
                    throw new IOException("Isi respons bukan file PDF yang valid.");
                }
                input.reset();
                pdf = Files.createTempFile("sppFKTP-", ".pdf");
                Files.copy(input, pdf, StandardCopyOption.REPLACE_EXISTING);
                pdf.toFile().deleteOnExit();
            }
            completed = true;
            return pdf;
        } finally {
            connection.disconnect();
            if (!completed && pdf != null) {
                Files.deleteIfExists(pdf);
            }
        }
    }
}
