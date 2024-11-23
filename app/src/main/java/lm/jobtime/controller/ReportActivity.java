package lm.jobtime.controller;

import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.view.View;
import android.widget.AdapterView;
import android.widget.ArrayAdapter;
import android.widget.Button;
import android.widget.Spinner;
import android.widget.TextView;
import android.widget.Toast;

import androidx.activity.EdgeToEdge;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.graphics.Insets;
import androidx.core.view.ViewCompat;
import androidx.core.view.WindowInsetsCompat;

import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.Calendar;
import java.util.Date;
import java.util.List;
import java.util.Locale;
import java.util.concurrent.Executors;

import lm.jobtime.R;
import lm.jobtime.database.AppDatabase;
import lm.jobtime.entity.SigningEntity;

public class ReportActivity extends AppCompatActivity {

    Spinner yearSpinner;
    Spinner monthSpinner;
    Spinner daySpinner;
    Button button;
    TextView report;
    TextView total;
    AppDatabase db;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        EdgeToEdge.enable(this);
        setContentView(R.layout.activity_report);

        db = AppDatabase.getInstance(getApplicationContext());

        yearSpinner = findViewById(R.id.year_spinner);
        monthSpinner = findViewById(R.id.month_spinner);
        daySpinner = findViewById(R.id.day_spinner);
        button = findViewById(R.id.button);
        report = findViewById(R.id.report_txt);
        total = findViewById(R.id.total_txt);

        populateYearSpinner();
        populateMonthSpinner();
        populateDaySpinner();

        monthSpinner.setOnItemSelectedListener(new AdapterView.OnItemSelectedListener() {
            @Override
            public void onItemSelected(AdapterView<?> adapterView, View view, int position, long l) {
                if (position != 0) {
                    populateDaysForSelectedMonth(position);
                }
            }

            @Override
            public void onNothingSelected(AdapterView<?> adapterView) {

            }
        });

        button.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View view) {
                String month = monthSpinner.getSelectedItem().toString();
                int day = Integer.parseInt(daySpinner.getSelectedItem().toString());
                String year = yearSpinner.getSelectedItem().toString();

                boolean isMonthDefault = month.equals("Mes");
                boolean isDayDefault = (day == 0);



                if (isMonthDefault && isDayDefault) {
                    obtenerSignings(signings -> {

                        showAllSignings(signings);
                        calculateTotalHours(signings, year);

                    });


                    //Toast.makeText(ReportActivity.this, "reporte anual", Toast.LENGTH_SHORT).show();
                } else if (!isMonthDefault && isDayDefault) {
                    Toast.makeText(ReportActivity.this, "reporte mensual", Toast.LENGTH_SHORT).show();
                } else {
                    Toast.makeText(ReportActivity.this, "reporte día", Toast.LENGTH_SHORT).show();
                }


            }
        });


        ViewCompat.setOnApplyWindowInsetsListener(findViewById(R.id.main), (v, insets) -> {
            Insets systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars());
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, systemBars.bottom);
            return insets;
        });
    }

    public void showAllSignings(List<SigningEntity> signings, String year, String month, int day) {
        StringBuilder reportBuilder = new StringBuilder();
        SimpleDateFormat sdf = new SimpleDateFormat("dd-MM-yyyy HH:mm:ss", Locale.getDefault());

        // Recorrer todos los fichajes y filtrar por año, mes y día
        for (SigningEntity signing : signings) {
            String[] signingDateParts = signing.getSigning().split(" ")[0].split("-");
            String signingYear = signingDateParts[2];
            String signingMonth = signingDateParts[1];
            String signingDay = signingDateParts[0];

            // Filtrar por año
            if (signingYear.equals(year)) {
                // Si el mes es diferente de "Mes", filtrar también por mes
                if (!month.equals("Mes") && !signingMonth.equals(month)) {
                    continue;
                }
                // Si el día es diferente de 0, filtrar también por día
                if (day != 0 && Integer.parseInt(signingDay) != day) {
                    continue;
                }

                try {
                    Date signingDate = sdf.parse(signing.getSigning());
                    reportBuilder.append("Fichaje: ").append(sdf.format(signingDate)).append("\n");
                } catch (ParseException e) {
                    e.printStackTrace();
                }
            }
        }

        // Mostrar el informe en el TextView
        if (reportBuilder.length() > 0) {
            report.setText(reportBuilder.toString());
        } else {
            report.setText("No se encontraron fichajes para el periodo seleccionado.");
        }
    }


    public void obtenerSignings(DataCallback<List<SigningEntity>> callback) {
        Executors.newSingleThreadExecutor().execute(() -> {
            // Obtener todos los fichajes en segundo plano
            List<SigningEntity> signings = db.daoSigning().getAllSingings();

            // Llamar al callback en el hilo principal
            new Handler(Looper.getMainLooper()).post(() -> {
                callback.onDataLoaded(signings);
            });
        });
    }

    public void calculateTotalHours(List<SigningEntity> signings, String year) {
        long totalMinutos = 0;
        SimpleDateFormat sdf = new SimpleDateFormat("dd-MM-yyyy HH:mm:ss", Locale.getDefault());

        for (int i=0; i<signings.size()-1; i+=2) {
            String entry = signings.get(i).getSigning();
            String exit = signings.get(i+1).getSigning();

            String entryDate = entry.split(" ")[0];
            String entryYear = entryDate.split("-")[2];

            if (entryYear.equals(year)) {
                try {
                    Date dateEntry = sdf.parse(entry);
                    Date dateExit = sdf.parse(exit);
                    long diff = dateExit.getTime() - dateEntry.getTime();
                    totalMinutos += diff / (1000 * 60);

                } catch (ParseException e){

                }
            }
        }
        total.setText("Se han trabajado en el año " + year + ": " + totalMinutos/60 + " horas y " + totalMinutos%60  + " minutos");

    }

    public void showAllSignings(List<SigningEntity> signings) {
        StringBuilder reportBuilder = new StringBuilder();

        SimpleDateFormat sdf = new SimpleDateFormat("dd-MM-yyyy HH:mm:ss", Locale.getDefault());

        // Recorrer todos los fichajes y mostrarlos
        for (SigningEntity signing : signings) {
            try {
                // Convertir la fecha de cada fichaje a un formato legible
                Date signingDate = sdf.parse(signing.getSigning());
                reportBuilder.append("Fichaje: ").append(sdf.format(signingDate)).append("\n");

            } catch (ParseException e) {
                e.printStackTrace();
            }
        }
        // Mostrar el informe en el TextView
        report.setText(reportBuilder.toString());
    }

    //nuevos metodos
    public void calculateTotalHours(List<SigningEntity> signings, String year, String month, int day) {
        long totalMinutos = 0;
        SimpleDateFormat sdf = new SimpleDateFormat("dd-MM-yyyy HH:mm:ss", Locale.getDefault());

        // Recorrer la lista de fichajes y filtrar por año, mes y día
        for (int i = 0; i < signings.size() - 1; i += 2) {
            String entry = signings.get(i).getSigning();
            String exit = signings.get(i + 1).getSigning();

            String[] entryDateParts = entry.split(" ")[0].split("-");
            String entryYear = entryDateParts[2];
            String entryMonth = entryDateParts[1];
            String entryDay = entryDateParts[0];

            // Filtrar por año
            if (entryYear.equals(year)) {
                // Si el mes es diferente de "Mes", filtrar también por mes
                if (!month.equals("Mes") && !entryMonth.equals(month)) {
                    continue;
                }
                // Si el día es diferente de 0, filtrar también por día
                if (day != 0 && Integer.parseInt(entryDay) != day) {
                    continue;
                }

                try {
                    Date dateEntry = sdf.parse(entry);
                    Date dateExit = sdf.parse(exit);
                    long diff = dateExit.getTime() - dateEntry.getTime();
                    totalMinutos += diff / (1000 * 60);

                } catch (ParseException e) {
                    e.printStackTrace();
                }
            }
        }

        long hours = totalMinutos / 60;
        long minutes = totalMinutos % 60;

        // Mostrar el resultado en el TextView
        String periodText = "Se han trabajado";
        if (!month.equals("Mes")) {
            periodText += " en " + month + " de " + year;
            if (day != 0) {
                periodText += " el día " + day;
            }
        } else {
            periodText += " en el año " + year;
        }

        total.setText(periodText + ": " + hours + " horas y " + minutes + " minutos");
    }


    // nuevos


    private void populateYearSpinner() {
        Calendar calendar = Calendar.getInstance();
        int currentYear = calendar.get(Calendar.YEAR);

        List<Integer> years = new ArrayList<>();
        for (int i = 0; i < 5; i++) {
            years.add(currentYear - i);
        }

        ArrayAdapter<Integer> yearAdapter = new ArrayAdapter<>(this, android.R.layout.simple_spinner_item, years);
        yearAdapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
        yearSpinner.setAdapter(yearAdapter);

        yearSpinner.setSelection(0);
    }

    // TODO ver la forma de llenar el spinner día al seleccionar un mes
    private void populateMonthSpinner() {
        List<String> months = Arrays.asList("Mes", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");
        ArrayAdapter<String> monthAdapter = new ArrayAdapter<>(this, android.R.layout.simple_spinner_item, months);
        monthAdapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
        monthSpinner.setAdapter(monthAdapter);
    }

    private void populateDaySpinner() {
        List<Integer> days = new ArrayList<>();
        days.add(0);
        ArrayAdapter<Integer> dayAdapter = new ArrayAdapter<>(this, android.R.layout.simple_spinner_item, days);
        dayAdapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
        daySpinner.setAdapter(dayAdapter);
    }

    private void populateDaysForSelectedMonth(int monthIndex) {
        int daysInMonth;
        switch (monthIndex) {
            case 2: // Febrero
                daysInMonth = 28; // Consideración básica, sin años bisiestos
                break;
            case 4: case 6: case 9: case 11: // Abril, Junio, Septiembre, Noviembre
                daysInMonth = 30;
                break;
            default: // Otros meses
                daysInMonth = 31;
                break;
        }

        List<Integer> days = new ArrayList<>();
        days.add(0); // Inicial con 0
        for (int i = 1; i <= daysInMonth; i++) {
            days.add(i);
        }

        ArrayAdapter<Integer> dayAdapter = new ArrayAdapter<>(this, android.R.layout.simple_spinner_item, days);
        dayAdapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
        daySpinner.setAdapter(dayAdapter);
    }

}