<form role="search"
      method="get"
      action="<?php echo esc_url(home_url('/')); ?>">

    <div class="input-group">

        <input
            type="search"
            class="form-control"
            placeholder="Pesquisar..."
            value="<?php echo get_search_query(); ?>"
            name="s">

        <button class="btn btn-primary" type="submit">
            Buscar
        </button>

    </div>

</form>