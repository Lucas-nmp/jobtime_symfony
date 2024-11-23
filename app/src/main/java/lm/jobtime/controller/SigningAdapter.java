package lm.jobtime.controller;

import android.content.Context;
import android.content.SharedPreferences;
import android.view.ContextMenu;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.view.animation.Animation;
import android.view.animation.AnimationUtils;
import android.widget.ImageView;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.core.content.ContextCompat;
import androidx.preference.PreferenceManager;
import androidx.recyclerview.widget.RecyclerView;

import java.util.ArrayList;
import java.util.List;

import lm.jobtime.R;
import lm.jobtime.entity.SigningEntity;

public class SigningAdapter extends RecyclerView.Adapter<SigningAdapter.ViewHolder> {

    private List<SigningEntity> listSignings;
    private Context context;




    public SigningAdapter(Context context, List<SigningEntity> signings) {
        this.listSignings = signings;
        this.context = context;


    }


    @NonNull
    @Override
    public SigningAdapter.ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_signing, parent, false);
        return new ViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull SigningAdapter.ViewHolder holder, int position) {
        SigningEntity signingEntity = listSignings.get(position);
        holder.createSigning(listSignings.get(position));


        int backgroundColor = getColorForBackground(signingEntity);
        holder.itemView.setBackgroundColor(backgroundColor);

        if (signingEntity.getEntrance()) {
            holder.imageView.setImageResource(R.drawable.entrance_foreground);
        } else {
            holder.imageView.setImageResource(R.drawable.exit_foreground);
        }

        // Aplica animación solo si el elemento es el último añadido
        if (position == 0) {
            Animation animation = AnimationUtils.loadAnimation(context, R.anim.item_entrance);
            holder.itemView.startAnimation(animation);

        }

    }

    private int getColorForBackground(SigningEntity  signingEntity) {

        if (signingEntity.getEntrance()) {
            return ContextCompat.getColor(context, R.color.entry);
        } else {
            return ContextCompat.getColor(context, R.color.exit);
        }
    }


    @Override
    public int getItemCount() {
        return listSignings.size();
    }

    public class ViewHolder extends RecyclerView.ViewHolder implements View.OnCreateContextMenuListener {

        TextView date;
        TextView time;
        ImageView imageView;


        public ViewHolder(@NonNull View itemView) {
            super(itemView);
            date = (TextView) itemView.findViewById(R.id.date_text);
            time = (TextView) itemView.findViewById(R.id.time_text);
            imageView = itemView.findViewById(R.id.image);
        }

        // TODO https://www.youtube.com/watch?v=y7gNVZ0JGOg  Con este vídeo he hecho el menú contextual de una forma diferente y
        // enseña como eliminar tareas y acceder a sus datos, en este caso la descripción.
        @Override
        public void onCreateContextMenu(ContextMenu contextMenu, View view, ContextMenu.ContextMenuInfo contextMenuInfo) {

        }

        public void createSigning(SigningEntity signingEntity) {
            String[] parts = signingEntity.getSigning().split(" ");
            String datePart = parts[0];
            String timePart = parts[1];
            date.setText(datePart);
            time.setText(timePart);
        }
    }
}
