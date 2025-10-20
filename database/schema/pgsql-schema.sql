--
-- PostgreSQL database dump
--

-- Dumped from database version 16.9
-- Dumped by pg_dump version 16.9

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: fn_brands_ad_log(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_brands_ad_log() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    INSERT INTO brand_logs (brand_id, accion, old_data)
    VALUES (
        OLD.id,
        'delete',
        json_build_object(
            'code', OLD.code,
            'name', OLD.name,
            'active', OLD.active
        )
    );
    RETURN NULL;
END; $$;


--
-- Name: fn_brands_ai_code_and_log(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_brands_ai_code_and_log() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_code text;
BEGIN
    IF NEW.code IS NULL OR NEW.code = '' THEN
        v_code := 'BR-' || lpad(NEW.id::text, 5, '0');
        UPDATE brands SET code = v_code WHERE id = NEW.id;
    END IF;

    INSERT INTO brand_logs (brand_id, accion, new_data)
    VALUES (
        NEW.id,
        'insert',
        json_build_object(
            'code', NEW.code,
            'name', NEW.name,
            'active', NEW.active
        )
    );

    RETURN NULL;
END; $$;


--
-- Name: fn_brands_au_log(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_brands_au_log() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    INSERT INTO brand_logs (brand_id, accion, old_data, new_data)
    VALUES (
        OLD.id,
        'update',
        json_build_object(
            'code', OLD.code,
            'name', OLD.name,
            'active', OLD.active
        ),
        json_build_object(
            'code', NEW.code,
            'name', NEW.name,
            'active', NEW.active
        )
    );
    RETURN NULL;
END; $$;


--
-- Name: fn_brands_bd_guard(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_brands_bd_guard() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF EXISTS (SELECT 1 FROM products WHERE brand_id = OLD.id) THEN
        RAISE EXCEPTION 'No se puede eliminar la marca "%": tiene productos asociados', OLD.name;
    END IF;

    RETURN OLD;
END; $$;


--
-- Name: fn_brands_bi_validate(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_brands_bi_validate() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_name text;
BEGIN
    -- Normalizar espacios y trim
    v_name := regexp_replace(coalesce(NEW.name, ''), '\s+', ' ', 'g');
    v_name := btrim(v_name);
    NEW.name := v_name;

    IF NEW.name IS NULL OR NEW.name = '' THEN
        RAISE EXCEPTION 'El nombre de la marca es obligatorio';
    END IF;

    -- Unicidad por lower(name), opcionalmente ignorando soft-deletes
    IF EXISTS (
        SELECT 1 FROM brands b
        WHERE lower(b.name) = lower(NEW.name)
        -- AND b.deleted_at IS NULL   -- <- descomentar si usás SoftDeletes en brands
    ) THEN
        RAISE EXCEPTION 'La marca "%" ya existe', NEW.name;
    END IF;

    -- active por defecto si viene nulo
    IF NEW.active IS NULL THEN
        NEW.active := true;
    END IF;

    RETURN NEW;
END; $$;


--
-- Name: fn_brands_bu_validate(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_brands_bu_validate() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_name text;
BEGIN
    -- No permitir cambiar code una vez asignado
    IF NEW.code IS DISTINCT FROM OLD.code AND OLD.code IS NOT NULL THEN
        RAISE EXCEPTION 'El código de la marca no puede modificarse';
    END IF;

    -- Si cambiaron el nombre, normalizar y validar unicidad
    IF NEW.name IS DISTINCT FROM OLD.name THEN
        v_name := regexp_replace(coalesce(NEW.name, ''), '\s+', ' ', 'g');
        v_name := btrim(v_name);
        NEW.name := v_name;

        IF NEW.name IS NULL OR NEW.name = '' THEN
            RAISE EXCEPTION 'El nombre de la marca es obligatorio';
        END IF;

        IF EXISTS (
            SELECT 1 FROM brands b
            WHERE lower(b.name) = lower(NEW.name)
              AND b.id <> OLD.id
            -- AND b.deleted_at IS NULL   -- <- descomentar si usás SoftDeletes
        ) THEN
            RAISE EXCEPTION 'La marca "%" ya existe', NEW.name;
        END IF;
    END IF;

    RETURN NEW;
END; $$;


--
-- Name: fn_categories_biu_validate(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_categories_biu_validate() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    -- Validación de NAME (case-insensitive), ignorando la propia fila en UPDATE
    IF NEW.name IS NULL OR btrim(NEW.name) = '' THEN
        RAISE EXCEPTION 'El nombre de la categoría es obligatorio';
    END IF;

    IF TG_OP = 'INSERT' THEN
        IF EXISTS (
            SELECT 1
            FROM public.categories c
            WHERE lower(c.name) = lower(NEW.name)
        ) THEN
            RAISE EXCEPTION 'Ya existe una categoría con ese nombre';
        END IF;
    ELSE
        -- UPDATE
        IF NEW.name IS DISTINCT FROM OLD.name AND EXISTS (
            SELECT 1
            FROM public.categories c
            WHERE lower(c.name) = lower(NEW.name)
              AND c.id <> OLD.id
        ) THEN
            RAISE EXCEPTION 'Ya existe una categoría con ese nombre';
        END IF;
    END IF;

    -- Validación de CODE (si viene manualmente)
    IF NEW.code IS NOT NULL AND btrim(NEW.code) <> '' THEN
        IF TG_OP = 'INSERT' THEN
            IF EXISTS (
                SELECT 1
                FROM public.categories c
                WHERE c.code = NEW.code
            ) THEN
                RAISE EXCEPTION 'El código ya está registrado en categorías';
            END IF;
        ELSE
            IF NEW.code IS DISTINCT FROM OLD.code AND EXISTS (
                SELECT 1
                FROM public.categories c
                WHERE c.code = NEW.code
                  AND c.id <> OLD.id
            ) THEN
                RAISE EXCEPTION 'El código ya está registrado en categorías';
            END IF;
        END IF;
    END IF;

    RETURN NEW;
END;
$$;


--
-- Name: fn_categories_set_code(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_categories_set_code() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    -- Solo si no vino un code manual
    IF NEW.code IS NULL OR btrim(NEW.code) = '' THEN
        UPDATE public.categories
           SET code = 'CAT-' || lpad(NEW.id::text, 5, '0')
         WHERE id = NEW.id;
    END IF;
    RETURN NULL; -- AFTER trigger
END;
$$;


--
-- Name: fn_clients_ad_log(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_clients_ad_log() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    INSERT INTO client_logs (client_id, accion, old_data)
    VALUES (
        OLD.id,
        'delete',
        json_build_object(
            'name', OLD.name,
            'email', OLD.email,
            'phone', OLD.phone,
            'code', OLD.code
        )
    );
    RETURN NULL;
END; $$;


--
-- Name: fn_clients_ai_log(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_clients_ai_log() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    INSERT INTO client_logs (client_id, accion, new_data)
    VALUES (
        NEW.id,
        'insert',
        json_build_object(
            'name', NEW.name,
            'email', NEW.email,
            'phone', NEW.phone,
            'code', NEW.code
        )
    );
    RETURN NULL;
END; $$;


--
-- Name: fn_clients_au_log(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_clients_au_log() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    INSERT INTO client_logs (client_id, accion, old_data, new_data)
    VALUES (
        OLD.id,
        'update',
        json_build_object(
            'name', OLD.name,
            'email', OLD.email,
            'phone', OLD.phone,
            'code', OLD.code
        ),
        json_build_object(
            'name', NEW.name,
            'email', NEW.email,
            'phone', NEW.phone,
            'code', NEW.code
        )
    );
    RETURN NULL;
END; $$;


--
-- Name: fn_clients_bd_guard(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_clients_bd_guard() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF EXISTS (SELECT 1 FROM sales WHERE client_id = OLD.id) THEN
        RAISE EXCEPTION 'No se puede eliminar un cliente con ventas registradas';
    END IF;

    IF EXISTS (SELECT 1 FROM credits WHERE client_id = OLD.id) THEN
        RAISE EXCEPTION 'No se puede eliminar un cliente con créditos registrados';
    END IF;

    RETURN OLD;
END; $$;


--
-- Name: fn_clients_bi_validate(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_clients_bi_validate() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    -- Email único (ignora soft-deleted)
    IF NEW.email IS NOT NULL AND EXISTS (
        SELECT 1 FROM clients c
        WHERE c.email = NEW.email
          AND c.deleted_at IS NULL
    ) THEN
        RAISE EXCEPTION 'El email ya está registrado en clientes';
    END IF;

    -- RUC único (ignora soft-deleted)
    IF NEW.ruc IS NOT NULL AND EXISTS (
        SELECT 1 FROM clients c
        WHERE c.ruc = NEW.ruc
          AND c.deleted_at IS NULL
    ) THEN
        RAISE EXCEPTION 'El RUC ya está registrado en clientes';
    END IF;

    -- CODE único si viene manualmente (ignora soft-deleted)
    IF NEW.code IS NOT NULL AND NEW.code <> '' AND EXISTS (
        SELECT 1 FROM clients c
        WHERE c.code = NEW.code
          AND c.deleted_at IS NULL
    ) THEN
        RAISE EXCEPTION 'El código ya está registrado en clientes';
    END IF;

    RETURN NEW;
END;
$$;


--
-- Name: fn_clients_bu_validate(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_clients_bu_validate() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF NEW.email IS DISTINCT FROM OLD.email AND NEW.email IS NOT NULL AND EXISTS (
        SELECT 1 FROM clients c
        WHERE c.email = NEW.email
          AND c.deleted_at IS NULL
          AND c.id <> OLD.id
    ) THEN
        RAISE EXCEPTION 'El email ya está registrado en clientes';
    END IF;

    IF NEW.ruc IS DISTINCT FROM OLD.ruc AND NEW.ruc IS NOT NULL AND EXISTS (
        SELECT 1 FROM clients c
        WHERE c.ruc = NEW.ruc
          AND c.deleted_at IS NULL
          AND c.id <> OLD.id
    ) THEN
        RAISE EXCEPTION 'El RUC ya está registrado en clientes';
    END IF;

    IF NEW.code IS DISTINCT FROM OLD.code AND NEW.code IS NOT NULL AND NEW.code <> '' AND EXISTS (
        SELECT 1 FROM clients c
        WHERE c.code = NEW.code
          AND c.deleted_at IS NULL
          AND c.id <> OLD.id
    ) THEN
        RAISE EXCEPTION 'El código ya está registrado en clientes';
    END IF;

    RETURN NEW;
END;
$$;


--
-- Name: fn_clients_set_code(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_clients_set_code() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    -- Si code no vino, establecerlo con prefijo y padding del id
    IF NEW.code IS NULL OR NEW.code = '' THEN
        UPDATE clients
           SET code = 'C' || lpad(NEW.id::text, 5, '0')
         WHERE id = NEW.id;
    END IF;
    RETURN NULL; -- AFTER trigger, no modifica NEW
END;
$$;


--
-- Name: fn_purchase_items_ad_stock(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_purchase_items_ad_stock() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
  IF EXISTS (SELECT 1 FROM purchases p WHERE p.id = OLD.purchase_id AND p.estado = 'aprobado') THEN
    UPDATE products SET stock = stock - COALESCE(OLD.qty,0) WHERE id = OLD.product_id;
  END IF;
  RETURN NULL;
END $$;


--
-- Name: fn_purchase_items_ai_stock(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_purchase_items_ai_stock() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
  IF EXISTS (SELECT 1 FROM purchases p WHERE p.id = NEW.purchase_id AND p.estado = 'aprobado') THEN
    UPDATE products SET stock = stock + COALESCE(NEW.qty,0) WHERE id = NEW.product_id;
  END IF;
  RETURN NULL;
END $$;


--
-- Name: fn_purchase_items_au_stock(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_purchase_items_au_stock() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE delta integer;
BEGIN
  IF EXISTS (SELECT 1 FROM purchases p WHERE p.id = NEW.purchase_id AND p.estado = 'aprobado') THEN
    IF NEW.product_id IS DISTINCT FROM OLD.product_id THEN
      UPDATE products SET stock = stock - COALESCE(OLD.qty,0) WHERE id = OLD.product_id;
      UPDATE products SET stock = stock + COALESCE(NEW.qty,0) WHERE id = NEW.product_id;
    ELSE
      delta := COALESCE(NEW.qty,0) - COALESCE(OLD.qty,0);
      IF delta <> 0 THEN
        UPDATE products SET stock = stock + delta WHERE id = NEW.product_id;
      END IF;
    END IF;
  END IF;
  RETURN NULL;
END $$;


--
-- Name: fn_purchases_au_estado_recalc(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_purchases_au_estado_recalc() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
  IF OLD.estado IS DISTINCT FROM NEW.estado THEN
    IF NEW.estado = 'aprobado' THEN
      UPDATE products p
         SET stock = p.stock + i.qty
        FROM purchase_items i
       WHERE i.product_id = p.id
         AND i.purchase_id = NEW.id;
    ELSIF OLD.estado = 'aprobado' AND NEW.estado <> 'aprobado' THEN
      UPDATE products p
         SET stock = p.stock - i.qty
        FROM purchase_items i
       WHERE i.product_id = p.id
         AND i.purchase_id = NEW.id;
    END IF;
  END IF;
  RETURN NULL;
END $$;


--
-- Name: fn_purchases_bd_adjust(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_purchases_bd_adjust() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
  IF OLD.estado = 'aprobado' THEN
    UPDATE products p
       SET stock = p.stock - i.qty
      FROM purchase_items i
     WHERE i.product_id = p.id
       AND i.purchase_id = OLD.id;
  END IF;
  RETURN OLD;
END $$;


--
-- Name: fn_sale_items_ad_stock(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_sale_items_ad_stock() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
  IF EXISTS (SELECT 1 FROM sales s WHERE s.id = OLD.sale_id AND s.estado = 'aprobado') THEN
    UPDATE products SET stock = stock + COALESCE(OLD.qty,0) WHERE id = OLD.product_id;
  END IF;
  RETURN NULL;
END $$;


--
-- Name: fn_sale_items_ai_stock(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_sale_items_ai_stock() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE _stock integer;
BEGIN
  IF EXISTS (SELECT 1 FROM sales s WHERE s.id = NEW.sale_id AND s.estado = 'aprobado') THEN
    SELECT stock INTO _stock FROM products WHERE id = NEW.product_id FOR UPDATE;
    IF COALESCE(_stock,0) < COALESCE(NEW.qty,0) THEN
      RAISE EXCEPTION 'Stock insuficiente (prod %, stock %, req %)', NEW.product_id, _stock, NEW.qty
        USING ERRCODE = '23514';
    END IF;
    UPDATE products SET stock = stock - COALESCE(NEW.qty,0) WHERE id = NEW.product_id;
  END IF;
  RETURN NULL;
END $$;


--
-- Name: fn_sale_items_au_stock(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_sale_items_au_stock() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE delta integer; _stock integer;
BEGIN
  IF EXISTS (SELECT 1 FROM sales s WHERE s.id = NEW.sale_id AND s.estado = 'aprobado') THEN
    IF NEW.product_id IS DISTINCT FROM OLD.product_id THEN
      UPDATE products SET stock = stock + COALESCE(OLD.qty,0) WHERE id = OLD.product_id;
      SELECT stock INTO _stock FROM products WHERE id = NEW.product_id FOR UPDATE;
      IF COALESCE(_stock,0) < COALESCE(NEW.qty,0) THEN
        RAISE EXCEPTION 'Stock insuficiente (prod %, stock %, req %)', NEW.product_id, _stock, NEW.qty
          USING ERRCODE = '23514';
      END IF;
      UPDATE products SET stock = stock - COALESCE(NEW.qty,0) WHERE id = NEW.product_id;
    ELSE
      delta := COALESCE(NEW.qty,0) - COALESCE(OLD.qty,0);
      IF delta <> 0 THEN
        IF delta > 0 THEN
          SELECT stock INTO _stock FROM products WHERE id = NEW.product_id FOR UPDATE;
          IF COALESCE(_stock,0) < delta THEN
            RAISE EXCEPTION 'Stock insuficiente (prod %, stock %, req +%)', NEW.product_id, _stock, delta
              USING ERRCODE = '23514';
          END IF;
        END IF;
        UPDATE products SET stock = stock - delta WHERE id = NEW.product_id;
      END IF;
    END IF;
  END IF;
  RETURN NULL;
END $$;


--
-- Name: fn_sales_au_estado_recalc(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_sales_au_estado_recalc() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE falta record;
BEGIN
  IF OLD.estado IS DISTINCT FROM NEW.estado THEN
    IF NEW.estado = 'aprobado' THEN
      -- Validar que ningún producto quede en negativo
      SELECT p.id AS product_id, p.stock, SUM(i.qty) AS requerido
        INTO falta
        FROM sale_items i
        JOIN products p ON p.id = i.product_id
       WHERE i.sale_id = NEW.id
       GROUP BY p.id, p.stock
       HAVING p.stock < SUM(i.qty)
       LIMIT 1;

      IF falta IS NOT NULL THEN
        RAISE EXCEPTION 'Stock insuficiente (prod %, stock %, req %)',
          falta.product_id, falta.stock, falta.requerido
          USING ERRCODE = '23514';
      END IF;

      -- Descontar todo en bulk
      UPDATE products p
         SET stock = p.stock - i.qty
        FROM sale_items i
       WHERE i.product_id = p.id
         AND i.sale_id = NEW.id;

    ELSIF OLD.estado = 'aprobado' AND NEW.estado <> 'aprobado' THEN
      -- Devolver stock en bulk
      UPDATE products p
         SET stock = p.stock + i.qty
        FROM sale_items i
       WHERE i.product_id = p.id
         AND i.sale_id = NEW.id;
    END IF;
  END IF;
  RETURN NULL;
END $$;


--
-- Name: fn_sales_bd_adjust(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_sales_bd_adjust() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
  IF OLD.estado = 'aprobado' THEN
    UPDATE products p
       SET stock = p.stock + i.qty
      FROM sale_items i
     WHERE i.product_id = p.id
       AND i.sale_id = OLD.id;
  END IF;
  RETURN OLD;
END $$;


--
-- Name: fn_suppliers_bd_guard(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_suppliers_bd_guard() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM public.purchases p
        WHERE p.supplier_id = OLD.id
    ) THEN
        RAISE EXCEPTION 'No se puede eliminar un proveedor con compras registradas. Use borrado lógico (soft delete).';
    END IF;
    RETURN OLD;
END;
$$;


--
-- Name: fn_suppliers_bi_set_code(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_suppliers_bi_set_code() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    -- Genera automáticamente el código si no se pasa manualmente
    IF NEW.code IS NULL THEN
        NEW.code := 'SUP-' || LPAD(NEW.id::text, 5, '0');
    END IF;

    RETURN NEW;
END;
$$;


--
-- Name: fn_suppliers_biu_validate(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_suppliers_biu_validate() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    -- NAME obligatorio
    IF NEW.name IS NULL OR btrim(NEW.name) = '' THEN
        RAISE EXCEPTION 'El nombre del proveedor es obligatorio';
    END IF;

    -- Unicidad NAME (case-insensitive), ignorando soft-deletes
    IF TG_OP = 'INSERT' THEN
        IF EXISTS (
            SELECT 1 FROM public.suppliers s
            WHERE lower(s.name) = lower(NEW.name)
              AND s.deleted_at IS NULL
        ) THEN
            RAISE EXCEPTION 'Ya existe un proveedor con ese nombre';
        END IF;
    ELSE
        IF NEW.name IS DISTINCT FROM OLD.name AND EXISTS (
            SELECT 1 FROM public.suppliers s
            WHERE lower(s.name) = lower(NEW.name)
              AND s.id <> OLD.id
              AND s.deleted_at IS NULL
        ) THEN
            RAISE EXCEPTION 'Ya existe un proveedor con ese nombre';
        END IF;
    END IF;

    -- Unicidad RUC (si no es NULL), ignorando soft-deletes
    IF NEW.ruc IS NOT NULL AND btrim(NEW.ruc) <> '' THEN
        IF TG_OP = 'INSERT' THEN
            IF EXISTS (
                SELECT 1 FROM public.suppliers s
                WHERE s.ruc = NEW.ruc
                  AND s.deleted_at IS NULL
            ) THEN
                RAISE EXCEPTION 'El RUC ya está registrado en proveedores';
            END IF;
        ELSE
            IF NEW.ruc IS DISTINCT FROM OLD.ruc AND EXISTS (
                SELECT 1 FROM public.suppliers s
                WHERE s.ruc = NEW.ruc
                  AND s.id <> OLD.id
                  AND s.deleted_at IS NULL
            ) THEN
                RAISE EXCEPTION 'El RUC ya está registrado en proveedores';
            END IF;
        END IF;
    END IF;

    RETURN NEW;
END;
$$;


--
-- Name: fn_suppliers_set_code(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_suppliers_set_code() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF NEW.code IS NULL OR btrim(NEW.code) = '' THEN
        UPDATE public.suppliers
           SET code = 'SUP-' || lpad(NEW.id::text, 5, '0')
         WHERE id = NEW.id;
    END IF;
    RETURN NULL; -- AFTER trigger
END;
$$;


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: brand_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.brand_logs (
    id bigint NOT NULL,
    brand_id bigint NOT NULL,
    accion character varying(20) NOT NULL,
    old_data json,
    new_data json,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: brand_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.brand_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: brand_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.brand_logs_id_seq OWNED BY public.brand_logs.id;


--
-- Name: brands; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.brands (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    code character varying(20)
);


--
-- Name: brands_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.brands_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: brands_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.brands_id_seq OWNED BY public.brands.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: categories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.categories (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    code character varying(20),
    deleted_at timestamp(0) without time zone
);


--
-- Name: categories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.categories_id_seq OWNED BY public.categories.id;


--
-- Name: client_documents; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_documents (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    type character varying(255) NOT NULL,
    file_path character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: client_documents_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_documents_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_documents_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_documents_id_seq OWNED BY public.client_documents.id;


--
-- Name: client_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_logs (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    accion character varying(20) NOT NULL,
    old_data json,
    new_data json,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: client_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_logs_id_seq OWNED BY public.client_logs.id;


--
-- Name: client_references; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_references (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    relation character varying(255),
    phone character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: client_references_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_references_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_references_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_references_id_seq OWNED BY public.client_references.id;


--
-- Name: clients; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.clients (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    phone character varying(255),
    address character varying(255),
    notes text,
    active smallint DEFAULT '1'::smallint NOT NULL,
    user_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    code character varying(20),
    deleted_at timestamp(0) without time zone,
    ruc character varying(20) NOT NULL
);


--
-- Name: clients_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.clients_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: clients_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.clients_id_seq OWNED BY public.clients.id;


--
-- Name: contacts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contacts (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    name character varying(100) NOT NULL,
    email character varying(100),
    phone character varying(20),
    "position" character varying(100),
    notes text,
    active smallint DEFAULT '1'::smallint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: contacts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contacts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contacts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contacts_id_seq OWNED BY public.contacts.id;


--
-- Name: credit_requirements; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.credit_requirements (
    id bigint NOT NULL,
    sale_id bigint NOT NULL,
    requirement character varying(255) NOT NULL,
    fulfilled boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: credit_requirements_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.credit_requirements_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: credit_requirements_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.credit_requirements_id_seq OWNED BY public.credit_requirements.id;


--
-- Name: credits; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.credits (
    id bigint NOT NULL,
    sale_id bigint NOT NULL,
    client_id bigint NOT NULL,
    amount numeric(14,2) NOT NULL,
    balance numeric(14,2) NOT NULL,
    due_date date NOT NULL,
    status character varying(255) DEFAULT 'pendiente'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT credits_status_check CHECK (((status)::text = ANY ((ARRAY['pendiente'::character varying, 'pagado'::character varying, 'vencido'::character varying])::text[])))
);


--
-- Name: credits_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.credits_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: credits_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.credits_id_seq OWNED BY public.credits.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: inventory_movements; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.inventory_movements (
    id bigint NOT NULL,
    product_id bigint NOT NULL,
    type character varying(255) NOT NULL,
    quantity integer NOT NULL,
    reason character varying(255),
    user_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT inventory_movements_type_check CHECK (((type)::text = ANY ((ARRAY['entrada'::character varying, 'salida'::character varying])::text[])))
);


--
-- Name: inventory_movements_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.inventory_movements_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: inventory_movements_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.inventory_movements_id_seq OWNED BY public.inventory_movements.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: payments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.payments (
    id bigint NOT NULL,
    credit_id bigint NOT NULL,
    amount numeric(14,2) NOT NULL,
    payment_date date NOT NULL,
    method character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: payments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.payments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: payments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.payments_id_seq OWNED BY public.payments.id;


--
-- Name: product_installments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.product_installments (
    id bigint NOT NULL,
    product_id bigint NOT NULL,
    installments integer NOT NULL,
    installment_price numeric(12,2) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: product_installments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.product_installments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: product_installments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.product_installments_id_seq OWNED BY public.product_installments.id;


--
-- Name: products; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.products (
    id bigint NOT NULL,
    code character varying(255),
    name character varying(255) NOT NULL,
    brand_id bigint NOT NULL,
    category_id bigint NOT NULL,
    supplier_id bigint NOT NULL,
    price_cash numeric(12,2),
    stock integer DEFAULT 0 NOT NULL,
    active boolean DEFAULT true NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: products_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.products_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: products_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.products_id_seq OWNED BY public.products.id;


--
-- Name: purchase_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.purchase_items (
    id bigint NOT NULL,
    purchase_id bigint NOT NULL,
    product_id bigint NOT NULL,
    qty integer NOT NULL,
    cost numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT purchase_items_qty_check CHECK ((qty > 0))
);


--
-- Name: purchase_items_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.purchase_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: purchase_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.purchase_items_id_seq OWNED BY public.purchase_items.id;


--
-- Name: purchases; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.purchases (
    id bigint NOT NULL,
    supplier_id bigint NOT NULL,
    purchased_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    estado character varying(255) DEFAULT 'pendiente'::character varying NOT NULL,
    invoice_number character varying(255),
    timbrado character varying(20),
    timbrado_expiration date,
    CONSTRAINT purchases_estado_check CHECK (((estado)::text = ANY ((ARRAY['pendiente'::character varying, 'aprobado'::character varying, 'rechazado'::character varying])::text[]))),
    CONSTRAINT purchases_estado_chk CHECK (((estado)::text = ANY ((ARRAY['pendiente'::character varying, 'aprobado'::character varying, 'rechazado'::character varying, 'cancelado'::character varying])::text[])))
);


--
-- Name: purchases_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.purchases_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: purchases_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.purchases_id_seq OWNED BY public.purchases.id;


--
-- Name: sale_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sale_items (
    id bigint NOT NULL,
    sale_id bigint NOT NULL,
    product_name character varying(255),
    unit_price numeric(14,2) NOT NULL,
    qty integer NOT NULL,
    iva_type character varying(10) NOT NULL,
    line_total numeric(14,2) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    product_id bigint,
    product_code character varying(100),
    CONSTRAINT sale_items_iva_type_check CHECK (((iva_type)::text = ANY ((ARRAY['10'::character varying, '5'::character varying, 'exento'::character varying])::text[])))
);


--
-- Name: sale_items_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.sale_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: sale_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.sale_items_id_seq OWNED BY public.sale_items.id;


--
-- Name: sales; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sales (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    modo_pago character varying(255) DEFAULT 'contado'::character varying NOT NULL,
    total numeric(12,2) NOT NULL,
    estado character varying(255) DEFAULT 'pendiente_aprobacion'::character varying NOT NULL,
    fecha date,
    nota text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    gravada_10 numeric(14,2) DEFAULT '0'::numeric NOT NULL,
    iva_10 numeric(14,2) DEFAULT '0'::numeric NOT NULL,
    gravada_5 numeric(14,2) DEFAULT '0'::numeric NOT NULL,
    iva_5 numeric(14,2) DEFAULT '0'::numeric NOT NULL,
    exenta numeric(14,2) DEFAULT '0'::numeric NOT NULL,
    total_iva numeric(14,2) DEFAULT '0'::numeric NOT NULL,
    CONSTRAINT sales_estado_chk CHECK (((estado)::text = ANY ((ARRAY['pendiente_aprobacion'::character varying, 'pendiente_aprobación'::character varying, 'aprobado'::character varying, 'rechazado'::character varying, 'editable'::character varying, 'cancelado'::character varying])::text[])))
);


--
-- Name: sales_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.sales_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: sales_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.sales_id_seq OWNED BY public.sales.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: suppliers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.suppliers (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    ruc character varying(255),
    phone character varying(255),
    email character varying(255),
    address character varying(255),
    active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    code character varying(20),
    deleted_at timestamp(0) without time zone
);


--
-- Name: suppliers_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.suppliers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: suppliers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.suppliers_id_seq OWNED BY public.suppliers.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: brand_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.brand_logs ALTER COLUMN id SET DEFAULT nextval('public.brand_logs_id_seq'::regclass);


--
-- Name: brands id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.brands ALTER COLUMN id SET DEFAULT nextval('public.brands_id_seq'::regclass);


--
-- Name: categories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories ALTER COLUMN id SET DEFAULT nextval('public.categories_id_seq'::regclass);


--
-- Name: client_documents id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_documents ALTER COLUMN id SET DEFAULT nextval('public.client_documents_id_seq'::regclass);


--
-- Name: client_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_logs ALTER COLUMN id SET DEFAULT nextval('public.client_logs_id_seq'::regclass);


--
-- Name: client_references id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_references ALTER COLUMN id SET DEFAULT nextval('public.client_references_id_seq'::regclass);


--
-- Name: clients id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clients ALTER COLUMN id SET DEFAULT nextval('public.clients_id_seq'::regclass);


--
-- Name: contacts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contacts ALTER COLUMN id SET DEFAULT nextval('public.contacts_id_seq'::regclass);


--
-- Name: credit_requirements id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credit_requirements ALTER COLUMN id SET DEFAULT nextval('public.credit_requirements_id_seq'::regclass);


--
-- Name: credits id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credits ALTER COLUMN id SET DEFAULT nextval('public.credits_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: inventory_movements id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_movements ALTER COLUMN id SET DEFAULT nextval('public.inventory_movements_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: payments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payments ALTER COLUMN id SET DEFAULT nextval('public.payments_id_seq'::regclass);


--
-- Name: product_installments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_installments ALTER COLUMN id SET DEFAULT nextval('public.product_installments_id_seq'::regclass);


--
-- Name: products id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products ALTER COLUMN id SET DEFAULT nextval('public.products_id_seq'::regclass);


--
-- Name: purchase_items id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.purchase_items ALTER COLUMN id SET DEFAULT nextval('public.purchase_items_id_seq'::regclass);


--
-- Name: purchases id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.purchases ALTER COLUMN id SET DEFAULT nextval('public.purchases_id_seq'::regclass);


--
-- Name: sale_items id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sale_items ALTER COLUMN id SET DEFAULT nextval('public.sale_items_id_seq'::regclass);


--
-- Name: sales id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sales ALTER COLUMN id SET DEFAULT nextval('public.sales_id_seq'::regclass);


--
-- Name: suppliers id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.suppliers ALTER COLUMN id SET DEFAULT nextval('public.suppliers_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: brand_logs brand_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.brand_logs
    ADD CONSTRAINT brand_logs_pkey PRIMARY KEY (id);


--
-- Name: brands brands_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.brands
    ADD CONSTRAINT brands_code_unique UNIQUE (code);


--
-- Name: brands brands_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.brands
    ADD CONSTRAINT brands_name_unique UNIQUE (name);


--
-- Name: brands brands_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.brands
    ADD CONSTRAINT brands_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: categories categories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_pkey PRIMARY KEY (id);


--
-- Name: client_documents client_documents_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_documents
    ADD CONSTRAINT client_documents_pkey PRIMARY KEY (id);


--
-- Name: client_logs client_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_logs
    ADD CONSTRAINT client_logs_pkey PRIMARY KEY (id);


--
-- Name: client_references client_references_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_references
    ADD CONSTRAINT client_references_pkey PRIMARY KEY (id);


--
-- Name: clients clients_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clients
    ADD CONSTRAINT clients_code_unique UNIQUE (code);


--
-- Name: clients clients_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clients
    ADD CONSTRAINT clients_email_unique UNIQUE (email);


--
-- Name: clients clients_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clients
    ADD CONSTRAINT clients_pkey PRIMARY KEY (id);


--
-- Name: clients clients_ruc_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clients
    ADD CONSTRAINT clients_ruc_unique UNIQUE (ruc);


--
-- Name: contacts contacts_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contacts
    ADD CONSTRAINT contacts_email_unique UNIQUE (email);


--
-- Name: contacts contacts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contacts
    ADD CONSTRAINT contacts_pkey PRIMARY KEY (id);


--
-- Name: credit_requirements credit_requirements_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credit_requirements
    ADD CONSTRAINT credit_requirements_pkey PRIMARY KEY (id);


--
-- Name: credits credits_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credits
    ADD CONSTRAINT credits_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: inventory_movements inventory_movements_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_movements
    ADD CONSTRAINT inventory_movements_pkey PRIMARY KEY (id);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: payments payments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_pkey PRIMARY KEY (id);


--
-- Name: product_installments product_installments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_installments
    ADD CONSTRAINT product_installments_pkey PRIMARY KEY (id);


--
-- Name: products products_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_code_unique UNIQUE (code);


--
-- Name: products products_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_pkey PRIMARY KEY (id);


--
-- Name: purchase_items purchase_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.purchase_items
    ADD CONSTRAINT purchase_items_pkey PRIMARY KEY (id);


--
-- Name: purchases purchases_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.purchases
    ADD CONSTRAINT purchases_pkey PRIMARY KEY (id);


--
-- Name: sale_items sale_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sale_items
    ADD CONSTRAINT sale_items_pkey PRIMARY KEY (id);


--
-- Name: sales sales_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sales
    ADD CONSTRAINT sales_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: suppliers suppliers_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.suppliers
    ADD CONSTRAINT suppliers_email_unique UNIQUE (email);


--
-- Name: suppliers suppliers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.suppliers
    ADD CONSTRAINT suppliers_pkey PRIMARY KEY (id);


--
-- Name: suppliers suppliers_ruc_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.suppliers
    ADD CONSTRAINT suppliers_ruc_unique UNIQUE (ruc);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: brands_name_lower_unique; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX brands_name_lower_unique ON public.brands USING btree (lower((name)::text));


--
-- Name: idx_purchase_items_product; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_purchase_items_product ON public.purchase_items USING btree (product_id);


--
-- Name: idx_purchase_items_purchase; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_purchase_items_purchase ON public.purchase_items USING btree (purchase_id);


--
-- Name: idx_purchases_estado; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_purchases_estado ON public.purchases USING btree (estado);


--
-- Name: idx_sale_items_product; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_sale_items_product ON public.sale_items USING btree (product_id);


--
-- Name: idx_sale_items_sale; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_sale_items_sale ON public.sale_items USING btree (sale_id);


--
-- Name: idx_sales_estado; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_sales_estado ON public.sales USING btree (estado);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: uq_categories_code; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX uq_categories_code ON public.categories USING btree (code);


--
-- Name: uq_categories_name_ci; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX uq_categories_name_ci ON public.categories USING btree (lower((name)::text));


--
-- Name: uq_suppliers_code; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX uq_suppliers_code ON public.suppliers USING btree (code) WHERE (code IS NOT NULL);


--
-- Name: uq_suppliers_name_ci; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX uq_suppliers_name_ci ON public.suppliers USING btree (lower((name)::text));


--
-- Name: uq_suppliers_ruc_notnull; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX uq_suppliers_ruc_notnull ON public.suppliers USING btree (ruc) WHERE (ruc IS NOT NULL);


--
-- Name: brands trg_brands_ad_log; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_brands_ad_log AFTER DELETE ON public.brands FOR EACH ROW EXECUTE FUNCTION public.fn_brands_ad_log();


--
-- Name: brands trg_brands_ai_code_and_log; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_brands_ai_code_and_log AFTER INSERT ON public.brands FOR EACH ROW EXECUTE FUNCTION public.fn_brands_ai_code_and_log();


--
-- Name: brands trg_brands_au_log; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_brands_au_log AFTER UPDATE ON public.brands FOR EACH ROW EXECUTE FUNCTION public.fn_brands_au_log();


--
-- Name: brands trg_brands_bd_guard; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_brands_bd_guard BEFORE DELETE ON public.brands FOR EACH ROW EXECUTE FUNCTION public.fn_brands_bd_guard();


--
-- Name: brands trg_brands_bi_validate; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_brands_bi_validate BEFORE INSERT ON public.brands FOR EACH ROW EXECUTE FUNCTION public.fn_brands_bi_validate();


--
-- Name: brands trg_brands_bu_validate; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_brands_bu_validate BEFORE UPDATE ON public.brands FOR EACH ROW EXECUTE FUNCTION public.fn_brands_bu_validate();


--
-- Name: categories trg_categories_biu_validate; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_categories_biu_validate BEFORE INSERT OR UPDATE ON public.categories FOR EACH ROW EXECUTE FUNCTION public.fn_categories_biu_validate();


--
-- Name: categories trg_categories_set_code; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_categories_set_code AFTER INSERT ON public.categories FOR EACH ROW EXECUTE FUNCTION public.fn_categories_set_code();


--
-- Name: clients trg_clients_ad_log; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_clients_ad_log AFTER DELETE ON public.clients FOR EACH ROW EXECUTE FUNCTION public.fn_clients_ad_log();


--
-- Name: clients trg_clients_ai_log; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_clients_ai_log AFTER INSERT ON public.clients FOR EACH ROW EXECUTE FUNCTION public.fn_clients_ai_log();


--
-- Name: clients trg_clients_au_log; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_clients_au_log AFTER UPDATE ON public.clients FOR EACH ROW EXECUTE FUNCTION public.fn_clients_au_log();


--
-- Name: clients trg_clients_bd_guard; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_clients_bd_guard BEFORE DELETE ON public.clients FOR EACH ROW EXECUTE FUNCTION public.fn_clients_bd_guard();


--
-- Name: clients trg_clients_bi_validate; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_clients_bi_validate BEFORE INSERT ON public.clients FOR EACH ROW EXECUTE FUNCTION public.fn_clients_bi_validate();


--
-- Name: clients trg_clients_bu_validate; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_clients_bu_validate BEFORE UPDATE ON public.clients FOR EACH ROW EXECUTE FUNCTION public.fn_clients_bu_validate();


--
-- Name: clients trg_clients_set_code; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_clients_set_code AFTER INSERT ON public.clients FOR EACH ROW EXECUTE FUNCTION public.fn_clients_set_code();


--
-- Name: purchase_items trg_purchase_items_ad; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_purchase_items_ad AFTER DELETE ON public.purchase_items FOR EACH ROW EXECUTE FUNCTION public.fn_purchase_items_ad_stock();


--
-- Name: purchase_items trg_purchase_items_ai; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_purchase_items_ai AFTER INSERT ON public.purchase_items FOR EACH ROW EXECUTE FUNCTION public.fn_purchase_items_ai_stock();


--
-- Name: purchase_items trg_purchase_items_au; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_purchase_items_au AFTER UPDATE ON public.purchase_items FOR EACH ROW EXECUTE FUNCTION public.fn_purchase_items_au_stock();


--
-- Name: purchases trg_purchases_au_estado; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_purchases_au_estado AFTER UPDATE ON public.purchases FOR EACH ROW EXECUTE FUNCTION public.fn_purchases_au_estado_recalc();


--
-- Name: purchases trg_purchases_bd_adjust; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_purchases_bd_adjust BEFORE DELETE ON public.purchases FOR EACH ROW EXECUTE FUNCTION public.fn_purchases_bd_adjust();


--
-- Name: sale_items trg_sale_items_ad; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_sale_items_ad AFTER DELETE ON public.sale_items FOR EACH ROW EXECUTE FUNCTION public.fn_sale_items_ad_stock();


--
-- Name: sale_items trg_sale_items_ai; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_sale_items_ai AFTER INSERT ON public.sale_items FOR EACH ROW EXECUTE FUNCTION public.fn_sale_items_ai_stock();


--
-- Name: sale_items trg_sale_items_au; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_sale_items_au AFTER UPDATE ON public.sale_items FOR EACH ROW EXECUTE FUNCTION public.fn_sale_items_au_stock();


--
-- Name: sales trg_sales_au_estado; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_sales_au_estado AFTER UPDATE ON public.sales FOR EACH ROW EXECUTE FUNCTION public.fn_sales_au_estado_recalc();


--
-- Name: sales trg_sales_bd_adjust; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_sales_bd_adjust BEFORE DELETE ON public.sales FOR EACH ROW EXECUTE FUNCTION public.fn_sales_bd_adjust();


--
-- Name: suppliers trg_suppliers_bd_guard; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_suppliers_bd_guard BEFORE DELETE ON public.suppliers FOR EACH ROW EXECUTE FUNCTION public.fn_suppliers_bd_guard();


--
-- Name: suppliers trg_suppliers_bi_set_code; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_suppliers_bi_set_code BEFORE INSERT ON public.suppliers FOR EACH ROW EXECUTE FUNCTION public.fn_suppliers_bi_set_code();


--
-- Name: suppliers trg_suppliers_biu_validate; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_suppliers_biu_validate BEFORE INSERT OR UPDATE ON public.suppliers FOR EACH ROW EXECUTE FUNCTION public.fn_suppliers_biu_validate();


--
-- Name: suppliers trg_suppliers_set_code; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_suppliers_set_code AFTER INSERT ON public.suppliers FOR EACH ROW EXECUTE FUNCTION public.fn_suppliers_set_code();


--
-- Name: sale_items 1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sale_items
    ADD CONSTRAINT "1" FOREIGN KEY (sale_id) REFERENCES public.sales(id) ON DELETE CASCADE;


--
-- Name: brand_logs brand_logs_brand_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.brand_logs
    ADD CONSTRAINT brand_logs_brand_id_foreign FOREIGN KEY (brand_id) REFERENCES public.brands(id) ON DELETE CASCADE;


--
-- Name: client_documents client_documents_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_documents
    ADD CONSTRAINT client_documents_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_logs client_logs_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_logs
    ADD CONSTRAINT client_logs_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_references client_references_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_references
    ADD CONSTRAINT client_references_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: clients clients_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clients
    ADD CONSTRAINT clients_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: contacts contacts_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contacts
    ADD CONSTRAINT contacts_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE RESTRICT;


--
-- Name: credit_requirements credit_requirements_sale_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credit_requirements
    ADD CONSTRAINT credit_requirements_sale_id_foreign FOREIGN KEY (sale_id) REFERENCES public.sales(id) ON DELETE CASCADE;


--
-- Name: credits credits_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credits
    ADD CONSTRAINT credits_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: credits credits_sale_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credits
    ADD CONSTRAINT credits_sale_id_foreign FOREIGN KEY (sale_id) REFERENCES public.sales(id) ON DELETE CASCADE;


--
-- Name: inventory_movements inventory_movements_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_movements
    ADD CONSTRAINT inventory_movements_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: inventory_movements inventory_movements_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_movements
    ADD CONSTRAINT inventory_movements_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: payments payments_credit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_credit_id_foreign FOREIGN KEY (credit_id) REFERENCES public.credits(id) ON DELETE CASCADE;


--
-- Name: product_installments product_installments_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_installments
    ADD CONSTRAINT product_installments_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: products products_brand_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_brand_id_foreign FOREIGN KEY (brand_id) REFERENCES public.brands(id) ON DELETE RESTRICT;


--
-- Name: products products_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE RESTRICT;


--
-- Name: products products_supplier_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_supplier_id_foreign FOREIGN KEY (supplier_id) REFERENCES public.suppliers(id) ON DELETE RESTRICT;


--
-- Name: purchase_items purchase_items_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.purchase_items
    ADD CONSTRAINT purchase_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE RESTRICT;


--
-- Name: purchase_items purchase_items_purchase_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.purchase_items
    ADD CONSTRAINT purchase_items_purchase_id_foreign FOREIGN KEY (purchase_id) REFERENCES public.purchases(id) ON DELETE CASCADE;


--
-- Name: purchases purchases_supplier_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.purchases
    ADD CONSTRAINT purchases_supplier_id_foreign FOREIGN KEY (supplier_id) REFERENCES public.suppliers(id) ON DELETE RESTRICT;


--
-- Name: sale_items sale_items_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sale_items
    ADD CONSTRAINT sale_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: sales sales_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sales
    ADD CONSTRAINT sales_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

--
-- PostgreSQL database dump
--

-- Dumped from database version 16.9
-- Dumped by pg_dump version 16.9

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2025_08_31_175618_create_clients_table	1
5	2025_09_13_003850_create_contacts_table	1
6	2025_09_14_235147_add_fields_to_contacts_table	1
7	2025_09_16_222738_create_sales_table	1
8	2025_09_17_000120_create_suppliers_table	1
9	2025_09_17_000121_create_brands_table	1
10	2025_09_17_000122_create_categories_table	1
11	2025_09_17_000123_create_products_table	1
12	2025_09_18_020424_create_purchases_table	1
13	2025_09_18_020425_create_purchase_items_table	1
14	2025_09_19_002253_add_code_and_softdeletes_to_clients_table	1
15	2025_09_19_005957_alter_sales_add_tax_breakdown	1
16	2025_09_19_005958_create_sale_items_table	1
17	2025_09_20_024703_make_ruc_unique_in_suppliers_table	1
18	2025_09_27_203524_alter_sale_items_add_product_id	1
19	2025_09_28_113607_add_product_code_to_sale_items_table	1
20	2025_09_28_132256_create_inventory_movements_table	1
21	2025_09_28_141951_add_estado_to_purchases_table	1
22	2025_09_28_171919_create_credits_table	1
23	2025_09_28_172021_create_payments_table	1
24	2025_09_29_125140_add_triggers_stock_full_management	2
25	2025_09_29_130504_fix_triggers_stock_management	3
26	2025_09_29_164412_add_triggers_clients_management	3
27	2025_09_29_164931_add_ruc_to_clients_table	3
28	2025_09_29_172124_update_trigger_clients_add_ruc_validation	4
29	2025_09_29_185223_add_invoice_number_to_purchases_table	4
30	2025_09_29_185837_add_timbrado_to_purchases_table	4
31	2025_09_29_192347_add_inventory_triggers	5
32	2025_09_29_201203_create_product_installments_table	5
33	2025_09_30_112425_create_client_references_table	5
34	2025_09_30_112447_create_client_documents_table	5
35	2025_09_30_112502_create_credit_requirements_table	5
36	2025_10_01_174156_add_trigger_clients	6
37	2025_10_01_182411_add_soft_deletes_to_categories	7
38	2025_10_01_185209_harden_suppliers_with_triggers_and_indexes	8
39	2025_10_02_001304_add_triggers_and_audit_to_brands	9
40	2025_10_02_234854_harden_full_stock_triggers	10
\.


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 40, true);


--
-- PostgreSQL database dump complete
--

