package lm.jobtime;

import android.app.AlertDialog;
import android.content.DialogInterface;
import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.os.Handler;
import android.text.Html;
import android.view.Menu;
import android.view.MenuItem;
import android.view.View;
import android.widget.Button;
import android.widget.Toast;

import androidx.activity.EdgeToEdge;
import androidx.annotation.NonNull;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.graphics.Insets;
import androidx.core.view.ViewCompat;
import androidx.core.view.WindowInsetsCompat;
import androidx.preference.PreferenceManager;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;
import java.util.List;
import java.util.Locale;
import java.util.concurrent.Executors;

import lm.jobtime.controller.ReportActivity;
import lm.jobtime.controller.SettingsActivity;
import lm.jobtime.controller.SigningAdapter;
import lm.jobtime.database.AppDatabase;
import lm.jobtime.entity.SigningEntity;

public class MainActivity extends AppCompatActivity {

    private List<SigningEntity> signings = new ArrayList<>();
    Button signing;

    private MenuItem menuItem;
    private SharedPreferences sharedPreferences;
    private Boolean entrance;

    // TODO añadir a la entidad un campo String para añadir observaciones y otro para url de un archivo
    // tipo imágen para justificantes y cosas así 

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        EdgeToEdge.enable(this);
        setContentView(R.layout.activity_main);
        sharedPreferences = PreferenceManager.getDefaultSharedPreferences(this);
        signing = (Button) findViewById(R.id.btn_signing);
        ViewCompat.setOnApplyWindowInsetsListener(findViewById(R.id.main), (v, insets) -> {
            Insets systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars());
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, systemBars.bottom);
            return insets;
        });

        AppDatabase db = AppDatabase.getInstance(getApplicationContext());
        addToRecycler(db);

        signing.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View view) {

                // Deshabilitar el botón para evitar pulsaciones repetidas
                signing.setEnabled(false);

                SigningEntity signingEntity = new SigningEntity();

                //SimpleDateFormat sdf = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.getDefault());
                SimpleDateFormat sdf = new SimpleDateFormat("dd-MM-yyyy HH:mm:ss", Locale.getDefault());
                String currentDateAndTime = sdf.format(new Date());

                // Asignar la fecha y hora actuales al campo signing
                signingEntity.setSigning(currentDateAndTime);

                // Cambiar el valor del boolean en SharedPreferences
                SharedPreferences.Editor editor = sharedPreferences.edit();
                boolean currentValue = sharedPreferences.getBoolean("next", false);
                editor.putBoolean("next", !currentValue); // Cambiar el valor a su opuesto
                editor.commit();

                // Asignar el valor a entrance
                entrance = currentValue;

                // Asignar el tipo de fichaje entrada/salida
                signingEntity.setEntrance(entrance);

                // Usar un Executor para realizar las operaciones en segundo plano
                Executors.newSingleThreadExecutor().execute(() -> {
                    // Insertar el fichaje en la base de datos
                    db.daoSigning().insert(signingEntity);

                    // Volver al hilo principal para actualizar el RecyclerView
                    //runOnUiThread(() -> addToRecycler(db));
                    runOnUiThread(() -> {
                        addToRecycler(db);

                        // Volver a habilitar el botón después de 2 segundos
                        new Handler().postDelayed(() -> signing.setEnabled(true), 5000);
                    });
                });



                Toast.makeText(MainActivity.this,R.string.correctsigning , Toast.LENGTH_SHORT).show();


            }
        });
    }

    @Override
    protected void onResume() {
        super.onResume();
        entrance = sharedPreferences.getBoolean("next", false);


    }

    private void addToRecycler(AppDatabase db) {
        Executors.newSingleThreadExecutor().execute(() -> {
            // Obtener todos los fichajes en segundo plano
            List<SigningEntity> signings = db.daoSigning().getAllSingingsDesc();

            // Actualizar el RecyclerView en el hilo principal
            runOnUiThread(() -> {
                SigningAdapter signingAdapter = new SigningAdapter(MainActivity.this, signings);
                RecyclerView recyclerView = findViewById(R.id.recyclerView);
                recyclerView.setLayoutManager(new LinearLayoutManager(MainActivity.this));
                recyclerView.setAdapter(signingAdapter);
            });
        });

    }

    // Hacer visible el menú
    @Override
    public boolean onCreateOptionsMenu(Menu menu) {
        getMenuInflater().inflate(R.menu.main_menu, menu);
        menu.setGroupVisible(R.id.options_menu, true);
        menu.setGroupVisible(R.id.actions_menu, true);


        return super.onCreateOptionsMenu(menu);
    }

    // Ejecutar acciones de los botones del menú
    @Override
    public boolean onOptionsItemSelected(@NonNull MenuItem item) {
        int id = item.getItemId();

        if (id == R.id.report) {
            startActivity(new Intent(this, ReportActivity.class));
        }

        if (id == R.id.options) {
            startActivity(new Intent(this, SettingsActivity.class));


        }

        if (id == R.id.go_out) {
            Toast.makeText(MainActivity.this, "Hasta pronto" , Toast.LENGTH_SHORT).show();
            finishAffinity();
        }

        if (id == R.id.about) {
            AlertDialog.Builder builder = new AlertDialog.Builder(this);
            builder.setTitle(getString(R.string.myname));
            builder.setMessage(Html.fromHtml("JobTime<br> IES Marques de Comares - 2024 <br><br> <b>Creador: Lucas Morandeira Parejo<b> "));
            builder.setPositiveButton(getString(R.string.accept), new DialogInterface.OnClickListener() {
                public void onClick(DialogInterface dialog, int which) {dialog.cancel();}
            });
            builder.show();
        }

        return super.onOptionsItemSelected(item);
    }
}