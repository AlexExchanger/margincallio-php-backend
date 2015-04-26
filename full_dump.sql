--
-- PostgreSQL database dump
--

SET statement_timeout = 0;
SET lock_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: 
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


SET search_path = public, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: account; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE account (
    id integer NOT NULL,
    "userId" integer,
    currency character varying(6) NOT NULL,
    status character varying(20) NOT NULL,
    guid character(36) NOT NULL,
    type character varying(80) NOT NULL,
    "creditLimit" numeric(18,8),
    "createdAt" integer NOT NULL,
    "publicId" character varying(510) DEFAULT NULL::character varying,
    gateway character varying(255),
    "tickerId" integer,
    balance numeric(24,15) DEFAULT 0 NOT NULL
);


ALTER TABLE account OWNER TO postgres;

--
-- Name: account_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE account_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE account_id_seq OWNER TO postgres;

--
-- Name: account_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE account_id_seq OWNED BY account.id;


--
-- Name: alarm_code_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE alarm_code_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE alarm_code_seq OWNER TO postgres;

--
-- Name: alarm_code; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE alarm_code (
    id bigint DEFAULT nextval('alarm_code_seq'::regclass) NOT NULL,
    "userId" integer,
    code character varying(250)
);


ALTER TABLE alarm_code OWNER TO postgres;

--
-- Name: candles_15m; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE candles_15m (
    id bigint NOT NULL,
    open numeric(20,15),
    close numeric(20,15),
    high numeric(20,15),
    low numeric(20,15),
    volume bigint,
    "timestamp" bigint,
    currency character varying(6)
);


ALTER TABLE candles_15m OWNER TO postgres;

--
-- Name: candles_1h; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE candles_1h (
    id bigint NOT NULL,
    open numeric(20,15),
    close numeric(20,15),
    high numeric(20,15),
    low numeric(20,15),
    volume bigint,
    "timestamp" bigint,
    currency character varying(6)
);


ALTER TABLE candles_1h OWNER TO postgres;

--
-- Name: candles_1m; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE candles_1m (
    id bigint NOT NULL,
    open numeric(20,15),
    close numeric(20,15),
    high numeric(20,15),
    low numeric(20,15),
    volume bigint,
    "timestamp" bigint,
    currency character varying(6)
);


ALTER TABLE candles_1m OWNER TO postgres;

--
-- Name: candles_5m; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE candles_5m (
    id bigint NOT NULL,
    open numeric(20,15),
    close numeric(20,15),
    high numeric(20,15),
    low numeric(20,15),
    volume bigint,
    "timestamp" bigint,
    currency character varying(6)
);


ALTER TABLE candles_5m OWNER TO postgres;

--
-- Name: coin_address_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE coin_address_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE coin_address_id_seq OWNER TO postgres;

--
-- Name: coin_address; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE coin_address (
    id integer DEFAULT nextval('coin_address_id_seq'::regclass) NOT NULL,
    address character varying(80) NOT NULL,
    "accountId" integer NOT NULL,
    "createdAt" integer NOT NULL,
    used boolean DEFAULT false,
    approve smallint DEFAULT 0,
    amount numeric(23,15),
    "transactionId" bigint,
    "lastTx" character varying(250) DEFAULT NULL::character varying
);


ALTER TABLE coin_address OWNER TO postgres;

--
-- Name: deal; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE deal (
    id bigint NOT NULL,
    size numeric(20,15) NOT NULL,
    price numeric(20,15) NOT NULL,
    "orderBuyId" bigint NOT NULL,
    "orderSellId" bigint NOT NULL,
    "createdAt" bigint NOT NULL,
    "userBuyId" bigint NOT NULL,
    "userSellId" bigint NOT NULL,
    "buyerFee" numeric(20,15) NOT NULL,
    "sellerFee" numeric(20,15) NOT NULL,
    side boolean NOT NULL,
    currency character varying(6)
);


ALTER TABLE deal OWNER TO postgres;

--
-- Name: file; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE file (
    id integer NOT NULL,
    uid character(32) NOT NULL,
    "fileName" character varying(510) NOT NULL,
    "fileSize" integer NOT NULL,
    "createdAt" integer NOT NULL,
    "createdBy" integer,
    "mimeType" character varying(510),
    "entityType" character varying(510) DEFAULT NULL::character varying,
    "entityId" integer
);


ALTER TABLE file OWNER TO postgres;

--
-- Name: file_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE file_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE file_id_seq OWNER TO postgres;

--
-- Name: file_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE file_id_seq OWNED BY file.id;


--
-- Name: gateway_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE gateway_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE gateway_id_seq OWNER TO postgres;

--
-- Name: gateway; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE gateway (
    id bigint DEFAULT nextval('gateway_id_seq'::regclass) NOT NULL,
    name character varying(250),
    currency character varying(5) NOT NULL,
    class character varying(250) NOT NULL,
    type character varying(250),
    payment text
);


ALTER TABLE gateway OWNER TO postgres;

--
-- Name: news; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE news (
    id integer NOT NULL,
    title character varying(510) NOT NULL,
    category character varying(510) NOT NULL,
    content text NOT NULL,
    preview text NOT NULL,
    "createdAt" integer NOT NULL,
    "createdBy" integer NOT NULL,
    "updatedAt" integer,
    "updatedBy" integer,
    "isActive" integer NOT NULL,
    number character varying(510) NOT NULL,
    "releaseDate" integer
);


ALTER TABLE news OWNER TO postgres;

--
-- Name: news_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE news_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE news_id_seq OWNER TO postgres;

--
-- Name: news_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE news_id_seq OWNED BY news.id;


--
-- Name: order; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE "order" (
    id bigint NOT NULL,
    "userId" bigint NOT NULL,
    size numeric(20,15) NOT NULL,
    price numeric(20,15),
    "createdAt" bigint NOT NULL,
    "updatedAt" bigint,
    status character varying(40) NOT NULL,
    type character varying(20) NOT NULL,
    side boolean NOT NULL,
    "offset" numeric(20,15),
    currency character varying(6),
    "actualSize" numeric(20,15)
);


ALTER TABLE "order" OWNER TO postgres;

--
-- Name: stat; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE stat (
    id integer NOT NULL,
    indicator character varying(510) DEFAULT NULL::character varying,
    "timestamp" integer,
    value numeric(24,15) DEFAULT NULL::numeric
);


ALTER TABLE stat OWNER TO postgres;

--
-- Name: stat_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE stat_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE stat_id_seq OWNER TO postgres;

--
-- Name: stat_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE stat_id_seq OWNED BY stat.id;


--
-- Name: system_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE system_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE system_id_seq OWNER TO postgres;

--
-- Name: system; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE system (
    id integer DEFAULT nextval('system_id_seq'::regclass) NOT NULL,
    name character varying(255),
    value text
);


ALTER TABLE system OWNER TO postgres;

--
-- Name: ticket; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE ticket (
    id integer NOT NULL,
    title character varying(510) NOT NULL,
    "createdBy" integer NOT NULL,
    "createdAt" integer NOT NULL,
    status character varying(30) NOT NULL,
    department character varying(40) NOT NULL,
    "updatedAt" integer,
    "updatedBy" integer,
    "messageCount" integer DEFAULT 0 NOT NULL,
    "userId" integer,
    importance character varying(250)
);


ALTER TABLE ticket OWNER TO postgres;

--
-- Name: ticket_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE ticket_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE ticket_id_seq OWNER TO postgres;

--
-- Name: ticket_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE ticket_id_seq OWNED BY ticket.id;


--
-- Name: ticket_message; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE ticket_message (
    id integer NOT NULL,
    "createdAt" integer NOT NULL,
    "createdBy" integer NOT NULL,
    "ticketId" integer NOT NULL,
    text text NOT NULL,
    files text
);


ALTER TABLE ticket_message OWNER TO postgres;

--
-- Name: ticket_message_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE ticket_message_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE ticket_message_id_seq OWNER TO postgres;

--
-- Name: ticket_message_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE ticket_message_id_seq OWNED BY ticket_message.id;


--
-- Name: transaction; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE transaction (
    id integer NOT NULL,
    account_from integer NOT NULL,
    amount numeric(23,15) NOT NULL,
    "createdAt" integer NOT NULL,
    hash character(16),
    currency character varying(5),
    account_to integer NOT NULL,
    user_from integer NOT NULL,
    user_to integer NOT NULL,
    side boolean DEFAULT false
);


ALTER TABLE transaction OWNER TO postgres;

--
-- Name: transaction_external_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE transaction_external_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE transaction_external_id_seq OWNER TO postgres;

--
-- Name: transaction_external; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE transaction_external (
    id integer DEFAULT nextval('transaction_external_id_seq'::regclass) NOT NULL,
    "accountId" integer,
    amount numeric(23,15),
    "createdAt" integer,
    hash character(16),
    currency character varying(5),
    "gatewayId" integer,
    type boolean,
    "verifyStatus" character varying(250),
    "verifiedBy" integer,
    details text
);


ALTER TABLE transaction_external OWNER TO postgres;

--
-- Name: transaction_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE transaction_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE transaction_id_seq OWNER TO postgres;

--
-- Name: transaction_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE transaction_id_seq OWNED BY transaction.id;


--
-- Name: user; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE "user" (
    id integer NOT NULL,
    password character varying(128),
    email character varying(510) NOT NULL,
    "lastLoginAt" integer,
    "emailVerification" character varying(128),
    "inviteCode" character varying(128),
    "createdAt" timestamp(6) with time zone DEFAULT now(),
    blocked boolean DEFAULT false,
    type character varying(30),
    "verifiedBy" integer,
    "verifiedData" character varying(30),
    "verifiedStatus" character varying(30),
    "verifiedReason" text,
    "twoFA" boolean DEFAULT false,
    "verifiedAt" integer,
    "referalPay" integer,
    "parentId" integer,
    "referalCode" character varying(100)
);


ALTER TABLE "user" OWNER TO postgres;

--
-- Name: user_early_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE user_early_seq
    START WITH 2
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE user_early_seq OWNER TO postgres;

--
-- Name: user_early; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE user_early (
    id bigint DEFAULT nextval('user_early_seq'::regclass) NOT NULL,
    email character varying(255),
    ip character varying(30)
);


ALTER TABLE user_early OWNER TO postgres;

--
-- Name: user_external_address_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE user_external_address_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE user_external_address_id_seq OWNER TO postgres;

--
-- Name: user_external_address; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE user_external_address (
    id integer DEFAULT nextval('user_external_address_id_seq'::regclass) NOT NULL,
    "userId" integer,
    currency character varying(5),
    address text,
    "accountId" integer
);


ALTER TABLE user_external_address OWNER TO postgres;

--
-- Name: user_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE user_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE user_id_seq OWNER TO postgres;

--
-- Name: user_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE user_id_seq OWNED BY "user".id;


--
-- Name: user_invite; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE user_invite (
    id character varying(128) NOT NULL,
    activated boolean DEFAULT false NOT NULL,
    email character varying(510) NOT NULL
);


ALTER TABLE user_invite OWNER TO postgres;

--
-- Name: user_log; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE user_log (
    id integer NOT NULL,
    "userId" integer,
    "createdAt" integer,
    action character varying(40) DEFAULT NULL::character varying,
    data text NOT NULL,
    ip character varying(30) DEFAULT NULL::character varying
);


ALTER TABLE user_log OWNER TO postgres;

--
-- Name: user_log_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE user_log_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE user_log_id_seq OWNER TO postgres;

--
-- Name: user_log_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE user_log_id_seq OWNED BY user_log.id;


--
-- Name: user_phone; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE user_phone (
    id character varying(128) NOT NULL,
    phone character varying(30) DEFAULT NULL::character varying
);


ALTER TABLE user_phone OWNER TO postgres;

--
-- Name: user_transaction_confurm_id; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE user_transaction_confurm_id
    START WITH 2
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE user_transaction_confurm_id OWNER TO postgres;

--
-- Name: user_transaction_confurm; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE user_transaction_confurm (
    id bigint DEFAULT nextval('user_transaction_confurm_id'::regclass) NOT NULL,
    code character varying(128),
    "userId" bigint,
    details text,
    used boolean DEFAULT false NOT NULL
);


ALTER TABLE user_transaction_confurm OWNER TO postgres;

--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY account ALTER COLUMN id SET DEFAULT nextval('account_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY file ALTER COLUMN id SET DEFAULT nextval('file_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY news ALTER COLUMN id SET DEFAULT nextval('news_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY stat ALTER COLUMN id SET DEFAULT nextval('stat_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY ticket ALTER COLUMN id SET DEFAULT nextval('ticket_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY ticket_message ALTER COLUMN id SET DEFAULT nextval('ticket_message_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY transaction ALTER COLUMN id SET DEFAULT nextval('transaction_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY "user" ALTER COLUMN id SET DEFAULT nextval('user_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY user_log ALTER COLUMN id SET DEFAULT nextval('user_log_id_seq'::regclass);


--
-- Data for Name: account; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY account (id, "userId", currency, status, guid, type, "creditLimit", "createdAt", "publicId", gateway, "tickerId", balance) FROM stdin;
1207	1166	BTC	opened	e701ccce-6920-254c-9eb2-506ee83b66f7	user.safeWallet	0.00000000	1426265634	\N	\N	\N	737.000000000000000
1208	1166	LTC	opened	2e1a9d21-8e58-5998-52d6-b481433645ee	user.safeWallet	0.00000000	1426265634	\N	\N	\N	0.000000000000000
1209	1166	DOGE	opened	14d62445-186a-12dc-ea81-d747d8e26468	user.safeWallet	0.00000000	1426265634	\N	\N	\N	0.000000000000000
1210	1166	EUR	opened	99770f71-b204-f825-7529-8ab26ad04e3b	user.withdrawWallet	0.00000000	1426265634	\N	\N	\N	0.000000000000000
1211	1166	BTC	opened	3c813128-7c20-1ab3-1035-8ac92ea6495a	user.withdrawWallet	0.00000000	1426265634	\N	\N	\N	0.000000000000000
1212	1166	LTC	opened	1cac495d-77eb-b385-c341-768c502ed097	user.withdrawWallet	0.00000000	1426265634	\N	\N	\N	0.000000000000000
1213	1166	DOGE	opened	14c84763-f334-5926-d339-2002d8610525	user.withdrawWallet	0.00000000	1426265634	\N	\N	\N	0.000000000000000
1279	1172	BTC	opened	27e99872-85f3-847e-b3c0-63aa7aad9079	user.safeWallet	0.00000000	1426265731	\N	\N	\N	0.000000000000000
1215	1166	BTC	opened	203d379f-576e-e355-1f15-682621b0ec8b	user.trading	0.00000000	1426265634	\N	\N	\N	263.000000000000000
1216	1166	LTC	opened	2ea3032f-dcce-4d46-b515-5b0cd3e6522e	user.trading	0.00000000	1426265634	\N	\N	\N	0.000000000000000
1217	1166	DOGE	opened	09e7c98a-a10a-31b0-94a3-96257123d28e	user.trading	0.00000000	1426265634	\N	\N	\N	0.000000000000000
1231	1168	BTC	opened	27f4bea7-dba8-743d-3b1e-9352dabfda3b	user.safeWallet	0.00000000	1426265662	\N	\N	\N	1000.000000000000000
1287	1172	BTC	opened	e2f8914f-9355-4bfc-b9e8-e90525871cd0	user.trading	0.00000000	1426265731	\N	\N	\N	1100.000000000000000
1232	1168	LTC	opened	d287d1ad-5f2a-e86e-09d7-cc7a57ff01e4	user.safeWallet	0.00000000	1426265662	\N	\N	\N	0.000000000000000
1233	1168	DOGE	opened	f57c90c7-2896-79f7-aa9e-8d8865e9a001	user.safeWallet	0.00000000	1426265662	\N	\N	\N	0.000000000000000
1234	1168	EUR	opened	583b8797-4d55-e903-b1c2-c3c4a4ab4c48	user.withdrawWallet	0.00000000	1426265662	\N	\N	\N	0.000000000000000
1235	1168	BTC	opened	418fcf24-c492-dddb-2e06-a7d503737d95	user.withdrawWallet	0.00000000	1426265662	\N	\N	\N	0.000000000000000
1236	1168	LTC	opened	1c1271fa-6a1f-6172-d7ef-7e8da6d1f653	user.withdrawWallet	0.00000000	1426265662	\N	\N	\N	0.000000000000000
923	0	DOGE	opened	958d8e5e-7f6e-43f1-8ae6-f037a79d5858	system.gateway.external.universe	\N	1426072217	\N	\N	\N	0.000000000000000
1237	1168	DOGE	opened	9ba173b6-e28e-5016-f07a-7c1e2bce5b16	user.withdrawWallet	0.00000000	1426265662	\N	\N	\N	0.000000000000000
1238	1168	EUR	opened	1a4fbf89-4848-62b9-c60d-0c89c2296338	user.trading	0.00000000	1426265662	\N	\N	\N	0.000000000000000
924	0	DOGE	opened	676666c3-fa20-4943-9d68-b73b93b8b455	system.gateway.external.universe.unknown	\N	1426072217	\N	\N	\N	0.000000000000000
925	0	DOGE	opened	3cc03057-6ea4-f963-bd37-f15befd81242	system.gateway.external	\N	1426072217	\N	\N	\N	0.000000000000000
1286	1172	EUR	opened	ebd27686-1082-4a16-a0ab-9a5815382e2b	user.trading	0.00000000	1426265731	\N	\N	\N	91829.386538000000000
927	0	DOGE	opened	1f3ad98b-e325-bd22-08fe-4c02292683b0	system.gateway.cold	\N	1426072217	\N	\N	\N	0.000000000000000
1239	1168	BTC	opened	5d2cdbf0-7820-b741-43d6-412cfb202a62	user.trading	0.00000000	1426265662	\N	\N	\N	0.000000000000000
1240	1168	LTC	opened	1318390d-1c04-8e80-0c7d-e2dd860e9643	user.trading	0.00000000	1426265662	\N	\N	\N	0.000000000000000
1241	1168	DOGE	opened	5ff0b90c-6da7-a703-2543-d30822060748	user.trading	0.00000000	1426265662	\N	\N	\N	0.000000000000000
1255	1170	BTC	opened	01b5fe91-3ec0-9af5-81ea-e5df01c9effa	user.safeWallet	0.00000000	1426265671	\N	\N	\N	1000.000000000000000
744	0	EUR	opened	dc7c210a-dc66-b3bb-0ed9-42c88d265a1d	system.gateway.internal	\N	1425658085	\N	\N	\N	144897.800000000000000
1256	1170	LTC	opened	a77c1b69-32c1-720b-3f14-6ed532fb8fca	user.safeWallet	0.00000000	1426265671	\N	\N	\N	0.000000000000000
1257	1170	DOGE	opened	ff2806e6-89c5-a81c-86fd-92a0bdd6393a	user.safeWallet	0.00000000	1426265671	\N	\N	\N	0.000000000000000
1258	1170	EUR	opened	51c95962-6351-5302-38d4-75353f86e5a6	user.withdrawWallet	0.00000000	1426265671	\N	\N	\N	0.000000000000000
1259	1170	BTC	opened	6252dc52-de5f-155b-910d-b225f8e47ad9	user.withdrawWallet	0.00000000	1426265671	\N	\N	\N	0.000000000000000
1260	1170	LTC	opened	acb88945-bf01-5993-bb10-49fa64b6785d	user.withdrawWallet	0.00000000	1426265671	\N	\N	\N	0.000000000000000
928	0	DOGE	opened	a20fb303-9750-41f6-9668-3a79d8db882c	system.gateway.hot	\N	1426072217	\N	\N	\N	0.000000000000000
929	0	DOGE	opened	c6e6ce6a-8bbe-fa1e-25bd-f31835d457d6	system.gateway.grant	\N	1426072217	\N	\N	\N	0.000000000000000
1261	1170	DOGE	opened	e5f071ab-fe6d-7b05-47b4-75e9ba6387fc	user.withdrawWallet	0.00000000	1426265671	\N	\N	\N	0.000000000000000
1262	1170	EUR	opened	7d396a8f-19ac-4ef3-21d2-ccd64f2382a0	user.trading	0.00000000	1426265671	\N	\N	\N	0.000000000000000
1263	1170	BTC	opened	bbf2c95f-5821-4f70-4e17-b6f9cb56be03	user.trading	0.00000000	1426265671	\N	\N	\N	0.000000000000000
741	0	EUR	opened	120eb418-80fc-2447-7766-3125ef187f47	system.gateway.external.universe	\N	1425658085	\N	\N	\N	0.000000000000000
742	0	EUR	opened	9ee4c48c-db60-27a6-513b-5b7d754440d1	system.gateway.external.universe.unknown	\N	1425658085	\N	\N	\N	0.000000000000000
743	0	EUR	opened	d99296c5-cce3-56f3-f0ce-6303ece559e9	system.gateway.external	\N	1425658085	\N	\N	\N	0.000000000000000
745	0	EUR	opened	a0395877-4cb6-9c06-390b-d9acf94b244f	system.gateway.cold	\N	1425658085	\N	\N	\N	0.000000000000000
746	0	EUR	opened	9d063ac1-ef8c-fe5c-f66d-30eb0abb96e6	system.gateway.hot	\N	1425658085	\N	\N	\N	0.000000000000000
747	0	EUR	opened	7fb19057-816c-bb42-fb06-b022b41aa3a8	system.gateway.grant	\N	1425658085	\N	\N	\N	0.000000000000000
1264	1170	LTC	opened	c5dc00ff-afa6-48fe-9cd7-7a30306104a8	user.trading	0.00000000	1426265671	\N	\N	\N	0.000000000000000
926	0	DOGE	opened	79b79037-b213-49df-d028-4127dbec7c31	system.gateway.internal	\N	1426072217	\N	\N	\N	0.000000000000000
1265	1170	DOGE	opened	62f93658-e145-51e9-4c2f-0dc515f50a8e	user.trading	0.00000000	1426265671	\N	\N	\N	0.000000000000000
1206	1166	EUR	opened	5f5ebff0-955d-c275-3268-a72a5b7a521d	user.safeWallet	0.00000000	1426265634	\N	\N	\N	49350.000000000000000
1214	1166	EUR	opened	6aaffbed-cebe-4cd5-1cc1-9c6434becd62	user.trading	0.00000000	1426265634	\N	\N	\N	50650.000000000000000
1280	1172	LTC	opened	0e511036-2742-1aa5-75f2-d61468ea5dd1	user.safeWallet	0.00000000	1426265731	\N	\N	\N	0.000000000000000
1281	1172	DOGE	opened	e9de6978-7445-18fd-ec18-be2427623ff0	user.safeWallet	0.00000000	1426265731	\N	\N	\N	0.000000000000000
1282	1172	EUR	opened	d4538240-3aeb-065f-636e-a5722acaafd0	user.withdrawWallet	0.00000000	1426265731	\N	\N	\N	0.000000000000000
1283	1172	BTC	opened	a2276803-fdb3-01f1-cf39-67e21e8ff7df	user.withdrawWallet	0.00000000	1426265731	\N	\N	\N	0.000000000000000
1284	1172	LTC	opened	4f940eab-0db8-0084-4ccf-5aa0c08fc03d	user.withdrawWallet	0.00000000	1426265731	\N	\N	\N	0.000000000000000
1285	1172	DOGE	opened	f7e8c79b-be35-007f-fa04-ea4301b149c3	user.withdrawWallet	0.00000000	1426265731	\N	\N	\N	0.000000000000000
1288	1172	LTC	opened	39bb0d3c-8dcf-76fd-f02f-53344de7d496	user.trading	0.00000000	1426265731	\N	\N	\N	0.000000000000000
1289	1172	DOGE	opened	baeeb3f8-777c-2c41-6728-08fd37438edb	user.trading	0.00000000	1426265731	\N	\N	\N	0.000000000000000
1230	1168	EUR	opened	4d98994c-4744-bda1-c7fd-a263a49825a0	user.safeWallet	0.00000000	1426265662	\N	\N	\N	100000.000000000000000
1219	1167	BTC	opened	c321da52-ec46-3819-2b44-0e6c7e085f48	user.safeWallet	0.00000000	1426265654	\N	\N	\N	1000.000000000000000
748	0	BTC	opened	c0dac2e3-21a1-1cd7-7540-b9187d0766c6	system.gateway.external.universe	\N	1425658843	\N	\N	\N	0.000000000000000
749	0	BTC	opened	a1dc6c04-0219-2f66-cd15-fc5be502dfd9	system.gateway.external.universe.unknown	\N	1425658843	\N	\N	\N	0.000000000000000
750	0	BTC	opened	156e15aa-beaf-56a4-1112-2b7a7c6db42f	system.gateway.external	\N	1425658843	\N	\N	\N	0.000000000000000
752	0	BTC	opened	166b05b3-f20c-bfdd-0f11-0d70632ca540	system.gateway.cold	\N	1425658843	\N	\N	\N	0.000000000000000
753	0	BTC	opened	068e2920-4e3f-022e-9e27-f5867d7e0539	system.gateway.hot	\N	1425658843	\N	\N	\N	0.000000000000000
754	0	BTC	opened	1eacc6ee-9428-e86e-ee3a-f337bdb3c730	system.gateway.grant	\N	1425658843	\N	\N	\N	0.000000000000000
751	0	BTC	opened	47b6345f-1935-2c8c-fa20-dce2c83413c4	system.gateway.internal	\N	1425658843	\N	\N	\N	1100.000000000000000
1220	1167	LTC	opened	2824a87c-b9ca-ba03-d9ee-61bbe88cc6f6	user.safeWallet	0.00000000	1426265654	\N	\N	\N	0.000000000000000
1221	1167	DOGE	opened	22e492af-fd34-6913-98a6-a65db6a810b5	user.safeWallet	0.00000000	1426265654	\N	\N	\N	0.000000000000000
1222	1167	EUR	opened	95a2d5d5-94e5-17fc-d9e0-042a256b8117	user.withdrawWallet	0.00000000	1426265654	\N	\N	\N	0.000000000000000
1223	1167	BTC	opened	6925440e-d06e-4db1-92fb-af65e8b532e3	user.withdrawWallet	0.00000000	1426265654	\N	\N	\N	0.000000000000000
1224	1167	LTC	opened	e94d0808-011e-9e32-c6b5-d0a6516b156b	user.withdrawWallet	0.00000000	1426265654	\N	\N	\N	0.000000000000000
1225	1167	DOGE	opened	3feba6eb-70c1-3ab1-a224-1b4a9ce716f4	user.withdrawWallet	0.00000000	1426265654	\N	\N	\N	0.000000000000000
1242	1169	EUR	opened	afb559f4-c9c5-7d7d-31ea-fe78331fc003	user.safeWallet	0.00000000	1426265659	\N	\N	\N	90000.000000000000000
1227	1167	BTC	opened	771a3658-979d-e9b9-397b-025b9be03620	user.trading	0.00000000	1426265654	\N	\N	\N	0.000000000000000
1228	1167	LTC	opened	8434eb18-9c0d-c9e2-ea7f-6aa49009521c	user.trading	0.00000000	1426265654	\N	\N	\N	0.000000000000000
1229	1167	DOGE	opened	75cbfbd0-e547-8d84-1482-b4e3a95c1c40	user.trading	0.00000000	1426265654	\N	\N	\N	0.000000000000000
1243	1169	BTC	opened	18612b19-e1ad-1cbd-39ec-246204609e6b	user.safeWallet	0.00000000	1426265659	\N	\N	\N	1000.000000000000000
1244	1169	LTC	opened	3518c5b9-b916-8916-002e-d621d4aba863	user.safeWallet	0.00000000	1426265659	\N	\N	\N	0.000000000000000
1245	1169	DOGE	opened	91320ed4-834b-07e3-c5bc-24c951bb1486	user.safeWallet	0.00000000	1426265659	\N	\N	\N	0.000000000000000
1246	1169	EUR	opened	7580b067-e4ec-663b-8abb-f7cf024fce26	user.withdrawWallet	0.00000000	1426265659	\N	\N	\N	0.000000000000000
1247	1169	BTC	opened	4fb968f6-34a9-8a40-0787-348f03bd9821	user.withdrawWallet	0.00000000	1426265659	\N	\N	\N	0.000000000000000
1248	1169	LTC	opened	b529a953-4a78-7492-68be-0710e63701f7	user.withdrawWallet	0.00000000	1426265659	\N	\N	\N	0.000000000000000
1249	1169	DOGE	opened	d3b8b4af-2931-7471-84c0-49a6ba09e08b	user.withdrawWallet	0.00000000	1426265659	\N	\N	\N	0.000000000000000
1278	1172	EUR	opened	d067ae3f-437e-8f5b-7c47-fec58b0aa9ba	user.safeWallet	0.00000000	1426265731	\N	\N	\N	0.000000000000000
1251	1169	BTC	opened	a6a97d0e-2bb1-20e8-d842-5d2f39af9faf	user.trading	0.00000000	1426265659	\N	\N	\N	0.000000000000000
1252	1169	LTC	opened	14f8aeb3-2e53-abb2-e13c-ea1fa25cd7a4	user.trading	0.00000000	1426265659	\N	\N	\N	0.000000000000000
1253	1169	DOGE	opened	70be7a90-3305-e2e9-a241-4a6af88bd762	user.trading	0.00000000	1426265659	\N	\N	\N	0.000000000000000
1267	1171	BTC	opened	3e4a6d57-eea9-a493-fb8f-3d6cbd6f1377	user.safeWallet	0.00000000	1426265673	\N	\N	\N	1000.000000000000000
1268	1171	LTC	opened	85edd776-3613-a86b-f2a0-bdf8617b2d86	user.safeWallet	0.00000000	1426265673	\N	\N	\N	0.000000000000000
1269	1171	DOGE	opened	b3de38bf-0fd1-7d70-1c5b-6e6d1f4d7be2	user.safeWallet	0.00000000	1426265673	\N	\N	\N	0.000000000000000
1270	1171	EUR	opened	0cd44fa4-f22b-6617-9983-77f4a2f1d8f5	user.withdrawWallet	0.00000000	1426265673	\N	\N	\N	0.000000000000000
1302	1174	EUR	opened	3bcb2156-e3f8-44e2-3fd2-4cec82f6ad08	user.safeWallet	0.00000000	1426270611	\N	\N	\N	0.000000000000000
1271	1171	BTC	opened	ed4215e0-acc9-3189-24a6-71c7375124ca	user.withdrawWallet	0.00000000	1426265673	\N	\N	\N	0.000000000000000
1272	1171	LTC	opened	9bf5d99d-07d1-6a1b-4c3d-033a38a32a40	user.withdrawWallet	0.00000000	1426265673	\N	\N	\N	0.000000000000000
1273	1171	DOGE	opened	8b5651d8-499b-865d-b25c-8a72cbd03050	user.withdrawWallet	0.00000000	1426265673	\N	\N	\N	0.000000000000000
1274	1171	EUR	opened	48ef1d39-c0fa-0bf7-0771-70d034ae76d3	user.trading	0.00000000	1426265673	\N	\N	\N	0.000000000000000
1275	1171	BTC	opened	9fe5a895-d840-2fee-4725-dcfc5ff31469	user.trading	0.00000000	1426265673	\N	\N	\N	0.000000000000000
1276	1171	LTC	opened	06bb412d-5e3f-c754-8e04-c220731b88be	user.trading	0.00000000	1426265673	\N	\N	\N	0.000000000000000
1277	1171	DOGE	opened	dfd9203b-0789-60c7-18c6-7768aa606093	user.trading	0.00000000	1426265673	\N	\N	\N	0.000000000000000
1291	1173	BTC	opened	a8a188d5-3b1f-29b1-abb3-c8fb98e2c9e5	user.safeWallet	0.00000000	1426265734	\N	\N	\N	1000.000000000000000
1292	1173	LTC	opened	4441f5cd-9d1b-285d-dce4-0ce184db0917	user.safeWallet	0.00000000	1426265734	\N	\N	\N	0.000000000000000
1293	1173	DOGE	opened	a19a44c4-ee5d-e381-44a4-67f7435023a6	user.safeWallet	0.00000000	1426265734	\N	\N	\N	0.000000000000000
1294	1173	EUR	opened	2bcc6918-18e1-980d-27de-2a0723f80583	user.withdrawWallet	0.00000000	1426265734	\N	\N	\N	0.000000000000000
1295	1173	BTC	opened	1e0d7a0b-8059-5f0a-5875-3114eb94161a	user.withdrawWallet	0.00000000	1426265734	\N	\N	\N	0.000000000000000
1296	1173	LTC	opened	3336d54a-57f2-87df-d6cd-7e2366826a14	user.withdrawWallet	0.00000000	1426265734	\N	\N	\N	0.000000000000000
1297	1173	DOGE	opened	11d6a2e8-b656-19c3-0eed-59db6dfeaa4c	user.withdrawWallet	0.00000000	1426265734	\N	\N	\N	0.000000000000000
1298	1173	EUR	opened	d2f83ac3-0be2-2669-a74d-e369e9ceeec8	user.trading	0.00000000	1426265734	\N	\N	\N	0.000000000000000
1299	1173	BTC	opened	791a728e-48fb-67cc-f4c3-9efa7df2593c	user.trading	0.00000000	1426265734	\N	\N	\N	0.000000000000000
1300	1173	LTC	opened	00de7250-8800-8b57-548e-2772edc92715	user.trading	0.00000000	1426265734	\N	\N	\N	0.000000000000000
1301	1173	DOGE	opened	bba09fd8-40ea-c256-be41-0264d29d87ca	user.trading	0.00000000	1426265734	\N	\N	\N	0.000000000000000
1254	1170	EUR	opened	c35006e5-c04c-3c72-dbef-585df6b9401e	user.safeWallet	0.00000000	1426265671	\N	\N	\N	100000.000000000000000
1250	1169	EUR	opened	a6ea5a15-8f3e-395d-1466-258e60df4dcc	user.trading	0.00000000	1426265659	\N	\N	\N	10000.000000000000000
1266	1171	EUR	opened	67a6229b-3702-fd90-67ba-e045d6f0f099	user.safeWallet	0.00000000	1426265673	\N	\N	\N	100000.000000000000000
1290	1173	EUR	opened	6a4554ea-ef46-b075-0418-1d8b1d8f179f	user.safeWallet	0.00000000	1426265734	\N	\N	\N	100000.000000000000000
1303	1174	BTC	opened	c688acda-a3c7-b98d-cf24-b3bc229a7d78	user.safeWallet	0.00000000	1426270611	\N	\N	\N	0.000000000000000
1304	1174	LTC	opened	ded30316-4a9a-2df5-50e4-322ff9d69926	user.safeWallet	0.00000000	1426270611	\N	\N	\N	0.000000000000000
1305	1174	DOGE	opened	bcfbd3e2-e14c-c523-8cab-f467caf8f592	user.safeWallet	0.00000000	1426270611	\N	\N	\N	0.000000000000000
1306	1174	EUR	opened	e954fdb9-44f5-5efe-6936-c9af907feaaf	user.withdrawWallet	0.00000000	1426270611	\N	\N	\N	0.000000000000000
1307	1174	BTC	opened	1bc39a53-46bc-597e-486a-366c1d4081c2	user.withdrawWallet	0.00000000	1426270611	\N	\N	\N	0.000000000000000
1218	1167	EUR	opened	36de2930-2199-f3ef-4a6c-caa272c84cad	user.safeWallet	0.00000000	1426265654	\N	\N	\N	99500.000000000000000
1308	1174	LTC	opened	c7398f72-6a73-cb69-136b-ab3a83be0ebc	user.withdrawWallet	0.00000000	1426270611	\N	\N	\N	0.000000000000000
1309	1174	DOGE	opened	232471c9-22f5-0df3-dc11-2bf1b15a3ef0	user.withdrawWallet	0.00000000	1426270611	\N	\N	\N	0.000000000000000
1310	1174	EUR	opened	b6991027-5af3-c165-6260-a366c1368474	user.trading	0.00000000	1426270611	\N	\N	\N	0.000000000000000
1311	1174	BTC	opened	3b25acaf-b249-a3b4-0b78-64034dd01122	user.trading	0.00000000	1426270611	\N	\N	\N	0.000000000000000
1312	1174	LTC	opened	4432b1ba-b4c5-3331-464d-7f8e1f2bcb74	user.trading	0.00000000	1426270611	\N	\N	\N	0.000000000000000
1313	1174	DOGE	opened	e07c6094-9244-a201-1f92-5029fa45b1fd	user.trading	0.00000000	1426270611	\N	\N	\N	0.000000000000000
1226	1167	EUR	opened	39e8c387-66d1-5a33-abe5-3210fa6309a9	user.trading	0.00000000	1426265654	\N	\N	\N	500.000000000000000
1314	1176	EUR	opened	8aeacddc-6549-3b3b-08f7-e806af78607a	user.safeWallet	0.00000000	1426525561	\N	\N	\N	0.000000000000000
1315	1176	BTC	opened	38920eaa-d5a0-44b1-52f4-225b19d48a3f	user.safeWallet	0.00000000	1426525561	\N	\N	\N	0.000000000000000
1316	1176	LTC	opened	1626ced5-9a0c-09bc-77ce-16c590ad13e2	user.safeWallet	0.00000000	1426525561	\N	\N	\N	0.000000000000000
1317	1176	DOGE	opened	e7441ae3-cf36-5b12-1bf5-2819f12b9b4b	user.safeWallet	0.00000000	1426525561	\N	\N	\N	0.000000000000000
1318	1176	EUR	opened	089bad0b-dffe-2d00-c473-4d6f1cf5b95a	user.withdrawWallet	0.00000000	1426525561	\N	\N	\N	0.000000000000000
1319	1176	BTC	opened	e163d441-19b4-2178-e4cf-0de58b2f35ec	user.withdrawWallet	0.00000000	1426525561	\N	\N	\N	0.000000000000000
1320	1176	LTC	opened	6754713a-2f8a-b804-2a82-702e01b063ee	user.withdrawWallet	0.00000000	1426525561	\N	\N	\N	0.000000000000000
1321	1176	DOGE	opened	8e991253-d49b-2173-0d9b-ca073ad43622	user.withdrawWallet	0.00000000	1426525561	\N	\N	\N	0.000000000000000
1322	1176	EUR	opened	2b009078-348e-6873-0c29-ed1bebc161f1	user.trading	0.00000000	1426525561	\N	\N	\N	0.000000000000000
1323	1176	BTC	opened	19f0fc4a-b6b5-132d-6765-908f0202eb71	user.trading	0.00000000	1426525561	\N	\N	\N	0.000000000000000
1324	1176	LTC	opened	9d48307f-2d22-c58d-4c09-8e397c417c30	user.trading	0.00000000	1426525561	\N	\N	\N	0.000000000000000
1325	1176	DOGE	opened	c8a57f60-764c-88b0-9d93-be66c23103bf	user.trading	0.00000000	1426525561	\N	\N	\N	0.000000000000000
1326	1178	EUR	opened	e8d7fb36-367f-33fd-6d36-6dd5c7dcf823	user.safeWallet	0.00000000	1426525854	\N	\N	\N	0.000000000000000
1327	1178	BTC	opened	5d5589c2-8d75-629e-2573-3d106e993b8c	user.safeWallet	0.00000000	1426525854	\N	\N	\N	0.000000000000000
1328	1178	LTC	opened	dfdf55c7-b625-a550-5d42-7ea3f213673f	user.safeWallet	0.00000000	1426525854	\N	\N	\N	0.000000000000000
1329	1178	DOGE	opened	cab4f0a0-1629-7254-3ebe-37944e0f8209	user.safeWallet	0.00000000	1426525854	\N	\N	\N	0.000000000000000
1330	1178	EUR	opened	c1e51fbc-389e-bae0-5150-1b1d7cd59c8a	user.withdrawWallet	0.00000000	1426525854	\N	\N	\N	0.000000000000000
1331	1178	BTC	opened	428a4734-f4d9-0250-2d92-93d6a9f1e445	user.withdrawWallet	0.00000000	1426525854	\N	\N	\N	0.000000000000000
1332	1178	LTC	opened	fc9134aa-3796-b4c7-6031-723b935f19d9	user.withdrawWallet	0.00000000	1426525854	\N	\N	\N	0.000000000000000
1333	1178	DOGE	opened	6bcd15ca-13f5-d0c1-b0cf-9a92705b24ce	user.withdrawWallet	0.00000000	1426525854	\N	\N	\N	0.000000000000000
1334	1178	EUR	opened	aa4c6749-5f10-355a-1310-0d9d9b3c19ce	user.trading	0.00000000	1426525854	\N	\N	\N	0.000000000000000
1335	1178	BTC	opened	43d90f6d-442f-34cc-70d0-09478889389c	user.trading	0.00000000	1426525854	\N	\N	\N	0.000000000000000
1336	1178	LTC	opened	a1377a32-4462-969b-4795-975eb1e31708	user.trading	0.00000000	1426525854	\N	\N	\N	0.000000000000000
1337	1178	DOGE	opened	48b22dc5-38ff-6b7f-5a26-2f2d4ed4f67f	user.trading	0.00000000	1426525854	\N	\N	\N	0.000000000000000
1339	1180	BTC	opened	7e4d8767-ccd6-ff38-aeec-7da959d68e2c	user.safeWallet	0.00000000	1426530030	\N	\N	\N	0.000000000000000
1340	1180	LTC	opened	949d22fa-85f6-d2f5-edc6-c3428895e60d	user.safeWallet	0.00000000	1426530030	\N	\N	\N	0.000000000000000
1341	1180	DOGE	opened	6fa999c6-1cc2-fe3a-8185-a2878237fafc	user.safeWallet	0.00000000	1426530030	\N	\N	\N	0.000000000000000
1342	1180	EUR	opened	84d59ac8-bf3a-85d0-5a39-81ce998dd37a	user.withdrawWallet	0.00000000	1426530030	\N	\N	\N	0.000000000000000
1343	1180	BTC	opened	d429b870-c40d-9587-da83-3b8f7ee63cd9	user.withdrawWallet	0.00000000	1426530030	\N	\N	\N	0.000000000000000
1344	1180	LTC	opened	d3343b00-7d42-e1fb-27bc-f58406de69c4	user.withdrawWallet	0.00000000	1426530030	\N	\N	\N	0.000000000000000
1345	1180	DOGE	opened	f93bcb3f-38a5-4d41-5906-8b0a6ca2e7a3	user.withdrawWallet	0.00000000	1426530030	\N	\N	\N	0.000000000000000
1347	1180	BTC	opened	2dff7faf-59ed-f4b7-ae19-b198fb1f1713	user.trading	0.00000000	1426530030	\N	\N	\N	0.000000000000000
1348	1180	LTC	opened	4fa20d77-6b2c-c476-3ede-fa5dafccf4eb	user.trading	0.00000000	1426530030	\N	\N	\N	0.000000000000000
1349	1180	DOGE	opened	d7808dee-8b6f-979a-3907-2aebdc968b89	user.trading	0.00000000	1426530030	\N	\N	\N	0.000000000000000
1350	1182	EUR	opened	11ab43c6-7229-1788-c1b8-e0be5c7240c9	user.safeWallet	0.00000000	1426609159	\N	\N	\N	0.000000000000000
1351	1182	BTC	opened	77c351db-d098-0850-111d-3931e5956d44	user.safeWallet	0.00000000	1426609159	\N	\N	\N	0.000000000000000
1352	1182	LTC	opened	bfc42114-4d45-221b-48d3-7ed236ee02eb	user.safeWallet	0.00000000	1426609159	\N	\N	\N	0.000000000000000
1353	1182	DOGE	opened	dd3d3198-59fc-4436-4cd6-c8704c8f9cf3	user.safeWallet	0.00000000	1426609159	\N	\N	\N	0.000000000000000
1354	1182	EUR	opened	da696eeb-958f-84e7-18b5-838915d60375	user.withdrawWallet	0.00000000	1426609159	\N	\N	\N	0.000000000000000
1355	1182	BTC	opened	a7d93366-ebf4-48d8-aa7a-e044c4149ad2	user.withdrawWallet	0.00000000	1426609159	\N	\N	\N	0.000000000000000
1356	1182	LTC	opened	3c584a4b-09c5-4157-00e9-27021488d12d	user.withdrawWallet	0.00000000	1426609159	\N	\N	\N	0.000000000000000
1357	1182	DOGE	opened	25cbd0bd-bf69-c59a-c873-45490e3c35a9	user.withdrawWallet	0.00000000	1426609159	\N	\N	\N	0.000000000000000
1358	1182	EUR	opened	b650085f-bb93-8154-244e-ad2fdd5cf957	user.trading	0.00000000	1426609159	\N	\N	\N	0.000000000000000
1359	1182	BTC	opened	fb7bbd4d-163d-b5c6-2ce3-4c712a8ca4eb	user.trading	0.00000000	1426609159	\N	\N	\N	0.000000000000000
1360	1182	LTC	opened	af56330d-418a-bd81-5325-a12bcaec088a	user.trading	0.00000000	1426609159	\N	\N	\N	0.000000000000000
1361	1182	DOGE	opened	5fb11b97-3388-83d5-3699-cb01cd07260a	user.trading	0.00000000	1426609159	\N	\N	\N	0.000000000000000
1362	1193	EUR	opened	53dc5254-031c-d363-5257-cb438ec561ca	user.safeWallet	0.00000000	1427135357	\N	\N	\N	0.000000000000000
2	0	EUR	opened	bfaa40ba-3cec-9488-a10b-a9d21ae7fbac	system.gateway.grant	\N	1422247712	\N	4	\N	0.000000000000000
1346	1180	EUR	opened	a29d0667-828c-824f-9a69-59a0ba86a088	user.trading	0.00000000	1426530030	\N	\N	\N	450.000000000000000
1338	1180	EUR	opened	c9ae96fa-d7c4-03e6-013f-97cb2ba7af4a	user.safeWallet	0.00000000	1426530030	\N	\N	\N	999550.000000000000000
1363	1193	BTC	opened	ec27ffff-8ebb-4b19-496e-cc171b162235	user.safeWallet	0.00000000	1427135357	\N	\N	\N	0.000000000000000
1364	1193	LTC	opened	fd3b549b-0606-af68-7a1b-e9b7b4bc63f1	user.safeWallet	0.00000000	1427135357	\N	\N	\N	0.000000000000000
1365	1193	DOGE	opened	160d2911-0807-bbd4-74e5-25858942849b	user.safeWallet	0.00000000	1427135357	\N	\N	\N	0.000000000000000
1366	1193	EUR	opened	974c9201-faa5-c130-0cbc-b832df01566a	user.withdrawWallet	0.00000000	1427135357	\N	\N	\N	0.000000000000000
1367	1193	BTC	opened	635c61b6-b8cc-3c0a-9adf-60463c6c02c1	user.withdrawWallet	0.00000000	1427135357	\N	\N	\N	0.000000000000000
1	0	BTC	opened	bfaa40ba-3cec-9488-a10b-a9d21ae7fbab	system.gateway.grant	\N	1422247711	\N	5	\N	-130.000000000000000
1368	1193	LTC	opened	5c2ab867-7151-45cf-6b6f-2b783cf6ce19	user.withdrawWallet	0.00000000	1427135357	\N	\N	\N	0.000000000000000
1369	1193	DOGE	opened	95c0b0fe-f118-c65d-2c2e-b8a70b25252f	user.withdrawWallet	0.00000000	1427135357	\N	\N	\N	0.000000000000000
1370	1193	EUR	opened	fe7cfb8c-9199-be70-a066-4d46ea936961	user.trading	0.00000000	1427135357	\N	\N	\N	0.000000000000000
1371	1193	BTC	opened	00866bf2-b836-33a9-2c9c-a6d392c81c21	user.trading	0.00000000	1427135357	\N	\N	\N	0.000000000000000
1372	1193	LTC	opened	c8d1d057-a930-49b0-d911-fd9e1421ffb9	user.trading	0.00000000	1427135357	\N	\N	\N	0.000000000000000
1373	1193	DOGE	opened	86be2182-af4e-541d-b870-2e7b8cf08e64	user.trading	0.00000000	1427135357	\N	\N	\N	0.000000000000000
1374	1194	EUR	opened	d37f1cc6-ffa5-0f27-aaa4-651c39e30868	user.safeWallet	0.00000000	1427136491	\N	\N	\N	0.000000000000000
1375	1194	BTC	opened	d68d1e76-d1a3-3c28-ef21-b3da4c7cd70b	user.safeWallet	0.00000000	1427136491	\N	\N	\N	0.000000000000000
1376	1194	LTC	opened	68bfb643-ef88-7864-a28a-7c2a17e04e47	user.safeWallet	0.00000000	1427136491	\N	\N	\N	0.000000000000000
1377	1194	DOGE	opened	35e817d0-b7c0-3b2b-89e2-561c1a4def33	user.safeWallet	0.00000000	1427136491	\N	\N	\N	0.000000000000000
1378	1194	EUR	opened	3191ff0f-fd24-d35f-5d06-e12c720fc8c5	user.withdrawWallet	0.00000000	1427136491	\N	\N	\N	0.000000000000000
1379	1194	BTC	opened	05856a42-85b6-2def-471b-cd095698f062	user.withdrawWallet	0.00000000	1427136491	\N	\N	\N	0.000000000000000
1380	1194	LTC	opened	a28a9fff-ce9f-113c-9392-20bc25bb9dd1	user.withdrawWallet	0.00000000	1427136491	\N	\N	\N	0.000000000000000
1381	1194	DOGE	opened	7c4070e8-d010-be1c-08ba-35bb45b8ad49	user.withdrawWallet	0.00000000	1427136491	\N	\N	\N	0.000000000000000
1382	1194	EUR	opened	fcbac93c-0b2b-9b62-2854-3c19bbd34715	user.trading	0.00000000	1427136491	\N	\N	\N	0.000000000000000
1383	1194	BTC	opened	1d6d0964-5717-4526-e0d1-742b7bba1abd	user.trading	0.00000000	1427136491	\N	\N	\N	0.000000000000000
1384	1194	LTC	opened	a476da20-3ee1-1cf9-3479-964dd330266a	user.trading	0.00000000	1427136491	\N	\N	\N	0.000000000000000
1385	1194	DOGE	opened	ebc90c26-db45-77dc-f6e3-11d0fd18ca3a	user.trading	0.00000000	1427136491	\N	\N	\N	0.000000000000000
1386	1195	EUR	opened	47c3c18b-ec1b-75c8-9ac0-2d464bdbc5a9	user.safeWallet	0.00000000	1427198458	\N	\N	\N	0.000000000000000
1387	1195	BTC	opened	bb481bda-878e-8aa8-fbf1-dc13207d02ba	user.safeWallet	0.00000000	1427198458	\N	\N	\N	0.000000000000000
1388	1195	LTC	opened	cf901d4a-045c-f17f-e888-3e2352fe0c6b	user.safeWallet	0.00000000	1427198458	\N	\N	\N	0.000000000000000
1389	1195	DOGE	opened	edc0670c-b3e1-023d-4244-49a154344d68	user.safeWallet	0.00000000	1427198458	\N	\N	\N	0.000000000000000
1390	1195	EUR	opened	52feda82-a51a-567c-2345-640e549ed766	user.withdrawWallet	0.00000000	1427198458	\N	\N	\N	0.000000000000000
1391	1195	BTC	opened	22363939-de68-a1d4-a46b-214a33dc69cd	user.withdrawWallet	0.00000000	1427198458	\N	\N	\N	0.000000000000000
1392	1195	LTC	opened	88cc3adb-2bda-8095-f378-1c9ab6f4fb03	user.withdrawWallet	0.00000000	1427198458	\N	\N	\N	0.000000000000000
1393	1195	DOGE	opened	4e9fb300-7089-4e93-4757-43ef12d7a184	user.withdrawWallet	0.00000000	1427198458	\N	\N	\N	0.000000000000000
1394	1195	EUR	opened	f594910a-5a7e-d557-d092-8b436bcea8b5	user.trading	0.00000000	1427198458	\N	\N	\N	0.000000000000000
1395	1195	BTC	opened	a57ec809-4262-f192-6d76-96eae78b05ef	user.trading	0.00000000	1427198458	\N	\N	\N	0.000000000000000
1396	1195	LTC	opened	ecc0bac4-bdd6-344c-20a5-608a91429a57	user.trading	0.00000000	1427198458	\N	\N	\N	0.000000000000000
1397	1195	DOGE	opened	a82ed2e2-9890-2af3-d6e4-5803f0988c39	user.trading	0.00000000	1427198458	\N	\N	\N	0.000000000000000
1398	0	LTC	opened	bfgdse0ba-3dgc-9488-a10b-a9d21ae7fb 	system.gateway.internal	\N	1422247711	\N	0	\N	0.000000000000000
1399	0	LTC	opened	bfgdse0ba-3dgc-9gs8-a10b-a9d21ae7fb 	system.gateway.external	\N	1422247711	\N	0	\N	0.000000000000000
1400	1196	EUR	opened	a405c9ea-e8c0-a001-36c7-89c35124a9b3	user.safeWallet	0.00000000	1427285620	\N	\N	\N	0.000000000000000
1412	1197	EUR	opened	7ab6beaa-4255-75a6-3aea-4ca06e6b8e32	user.safeWallet	0.00000000	1427300433	\N	\N	\N	0.000000000000000
1402	1196	LTC	opened	d940a196-5e22-e23e-2cae-0b38905b5858	user.safeWallet	0.00000000	1427285620	\N	\N	\N	0.000000000000000
1403	1196	DOGE	opened	a87dab4f-a507-01c7-f52e-0920ab73b2ed	user.safeWallet	0.00000000	1427285620	\N	\N	\N	0.000000000000000
1404	1196	EUR	opened	2f819ce1-70b3-8b5d-f9ab-8323056ef85f	user.withdrawWallet	0.00000000	1427285620	\N	\N	\N	0.000000000000000
1405	1196	BTC	opened	db3eb3d2-8577-2a8c-4841-6035e27b9305	user.withdrawWallet	0.00000000	1427285620	\N	\N	\N	0.000000000000000
1406	1196	LTC	opened	cbb38208-00e0-527d-b199-1d1c50793e30	user.withdrawWallet	0.00000000	1427285620	\N	\N	\N	0.000000000000000
\.


--
-- Name: account_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('account_id_seq', 1711, true);


--
-- Data for Name: alarm_code; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY alarm_code (id, "userId", code) FROM stdin;
\.


--
-- Name: alarm_code_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('alarm_code_seq', 1, false);


--
-- Data for Name: candles_15m; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY candles_15m (id, open, close, high, low, volume, "timestamp", currency) FROM stdin;
\.


--
-- Data for Name: candles_1h; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY candles_1h (id, open, close, high, low, volume, "timestamp", currency) FROM stdin;
\.


--
-- Data for Name: candles_1m; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY candles_1m (id, open, close, high, low, volume, "timestamp", currency) FROM stdin;
\.


--
-- Data for Name: candles_5m; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY candles_5m (id, open, close, high, low, volume, "timestamp", currency) FROM stdin;
\.


--
-- Data for Name: coin_address; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY coin_address (id, address, "accountId", "createdAt", used, approve, amount, "transactionId", "lastTx") FROM stdin;
19	1L8joKRJvk2yGJFBtB3fHziKjiEUVbTAHT	792	1425985187	f	0	\N	\N	\N
20	1N9KkoJavjQ7GBRoiaNYx3wdP4fZRcNFNL	864	1426072251	f	0	\N	\N	\N
21	19cLHY3j9wv5L3VduBNDbBKXaZEccZBwn2	1207	1426267192	f	0	\N	\N	\N
22	16KfuYdBYM5QJkzVvLE1W1rdKFMuZdTGXX	1279	1426537262	f	0	\N	\N	\N
23	1PL2Emy69SKqq88ThBt2Akhf1aaa3LqdpP	1387	1427200530	f	0	\N	\N	\N
24	191mo9MFigHqqA3RSUQ7HkLUXnFEQF3N5X	1401	1427285665	t	0	\N	62	b3064f0f7d0e600ca13081516a18b6c36f1080aa3d5beb7300cad457fb51de8d
\.


--
-- Name: coin_address_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('coin_address_id_seq', 24, true);


--
-- Data for Name: deal; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY deal (id, size, price, "orderBuyId", "orderSellId", "createdAt", "userBuyId", "userSellId", "buyerFee", "sellerFee", side, currency) FROM stdin;
1	0.333300000000000	275.000000000000000	1	3	635618637676553750	1166	1166	0.316635000000000	87.074625000000000	f	BTC
2	0.000030000000000	275.000000000000000	1	4	635618638538585000	1166	1166	0.000028500000000	0.007837500000000	f	BTC
3	269.999970000000000	1.000000000000000	5	4	635618638765928750	1166	1166	256.499971500000000	256.499971500000000	t	BTC

\.


--
-- Data for Name: file; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY file (id, uid, "fileName", "fileSize", "createdAt", "createdBy", "mimeType", "entityType", "entityId") FROM stdin;
112	d1c0a86c7cea796a912a6e4a4406fd8f	photo_2015-04-03_18-56-24.jpg	131783	1428153629	1166	\N	ticket	\N
\.


--
-- Name: file_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('file_id_seq', 112, true);


--
-- Data for Name: gateway; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY gateway (id, name, currency, class, type, payment) FROM stdin;
5	BTC Grant	BTC	BtcGrantGateway	admin	\N
4	USD Grant	EUR	UsdGrantGateway	admin	\N
2	Bitcoin	BTC	BtcGateway	user	{"in":[{"name":"BTC In","fields":[{"title":"Address","name":"address","type":"Text","value":"ADDRESS_PLACEHOLDER"},{"title":"QR ","name":"Qr","type":"Qr","value":"ADDRESS_PLACEHOLDER"}]}],"out":[{"name":"BTC Out","fields":[{"title":"Address","name":"address","type":"String","required":true,"value":""}]}]}
6	Euro Bank	EUR	EurBankGateway	user	{"in":[{"name":"Euro In","fields":[{"title":"Beneficiary","name":"beneficiary","type":"Text","value":"SCAM LIMITED"},{"title":"Beneficiary Address","name":"address","type":"Text","value":"869 HIGH ROAD, LONDON N12"},{"title":"Beneficiary IBAN","name":"iban","type":"Text","value":"LVNUH98W82U349J2893"},{"title":"Beneficiary Bank","name":"bank","type":"Text","value":"Baltic International Bank"},{"title":"Beneficiary Bank Address","name":"bank_address","type":"Text","value":"Kaleju St.43, Riga"},{"title":"Beneficiary Bank SWIFT","name":"bank_swift","type":"Text","value":"BLIBLV22"},{"title":"Details of payment","name":"details_payment","type":"Text","value":"Person: USERNAME_PLACEHOLDER"},{"title":"Correspondent bank","name":"cor_bank","type":"Text","value":"Deutsche bank AG "},{"title":"City","name":"city","type":"Text","value":"Frankfurt / M"},{"title":"Country","name":"country","type":"Text","value":"Germany"},{"title":"Correspondent bank SWIFT","name":"co_swift","type":"Text","value":"DEUTDEFF"},{"title":"Fee","name":"fee","type":"Text","value":"1% (min fee 15€)"},{"title":"Min sum allowed","name":"min_sum","type":"Text","value":"2000€"}]}],"out":[{"name":"Euro Out","fields":[{"title":"Beneficiary Name","name":"beneficiary","type":"String","required":true,"value":""},{"title":"Beneficiary IBAN","name":"iban","type":"String","required":true,"value":""},{"title":"Beneficiary SWIFT","name":"swift","type":"String","required":true,"value":""},{"title":"Beneficiary Address","name":"address","type":"String","required":true,"value":""},{"title":"Name of bank","name":"bank_name","type":"String","required":true,"value":""},{"title":"Bank Address","name":"bank_address","type":"String","required":true,"value":""}]}]}
7	Euro Admin	EUR	EurAdminBankGateway	admin	
\.


--
-- Name: gateway_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('gateway_id_seq', 1, false);


--
-- Data for Name: news; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY news (id, title, category, content, preview, "createdAt", "createdBy", "updatedAt", "updatedBy", "isActive", number, "releaseDate") FROM stdin;
41	Вставай, братишка, у нас осталась пара грамм	info	ПАЦАНЫ, Я СЕГОДНЯ ШЁЛ КОРОЧЕ ПО ОФИСУ И УВИДЕЛ ЗАПУЩЕННУЮ СПЕРМЕРКУ А РЯДОМ КЛОУНА В МАЙКЕ «ENTERPRISE PROGRAMMER», НУ Я ПОДСКОЧИЛ И РЕЗКО ДЕИНСТАЛЬНУЛ НА МАШИНЕ NЕТ ФРЕЙМВОРК К ХУЯМ: И ПОЯСНИЛ ЕГО КРИКОМ «НЕ ЛЮБЛЮ УПРАВЛЯЕМЫЙ КОД», ПОТОМУ ЧТО Я УГОРЕЛ ПО ДИСТРИБУЦИИ РЕГИСТРОВ, ПАЦАНЫ ДУХ СТАРОЙ ШКОЛЫ ЖИВЁТ ТОЛЬКО В СТАТИЧЕСКИ ЛИНКУЕМЫХ ЛИБАХ, ГДЕ ИНКРЕМЕНТИРУЮТ УКАЗАТЕЛИ, ГДЕ КОДЕРЫ ЖИВУТ KERNEL.DLL, USER32.DLL И ЕБАШАТ АНСЕЙВ КОД! ТОЛЬКО ПУР CИ, ТОЛЬКО ФАСМ!!! ЮНИТИ УЛЬТРАХОРДКОР mov edx, dword [esp+4*eax+8]!!! Пацаны, компиляйте в нейтив, дебажте идой, прописывайте относительные смещения, сбрасывайте регистры флагов, цените свободу! ПИШИТЕ БЛОКНОТОМ СМЕЛО И ПРЯМО В БИНАРНИК! 0xDEADBEEF!	shl edx,3\nshr edx,3 ёто нахуя?	1426291069	1172	1426439778	1172	1	1	\N
\.


--
-- Name: news_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('news_id_seq', 41, true);


--
-- Data for Name: order; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY "order" (id, "userId", size, price, "createdAt", "updatedAt", status, type, side, "offset", currency, "actualSize") FROM stdin;
56	1	13.238716109999800	1.292639421658890	635656727907588080	635656727934620052	filled	LIMIT	f	\N	LTC	0.000000000000000
3	1166	0.333300000000000	275.000000000000000	635618637676553750	635618637676553750	filled	MARKET	t	\N	BTC	0.000000000000000
29	1	0.563207053608823	279.666423697000000	635618657148116250	635618657148116250	filled	LIMIT	t	\N	BTC	0.000000000000000
37	1	1.938756259362560	287.136174619392000	635618657387647500	635656727942745266	filled	LIMIT	t	\N	BTC	0.000000000000000
4	1166	270.000000000000000	1.000000000000000	635618638538585000	635618638765928750	filled	LIMIT	t	\N	BTC	0.000000000000000
5	1166	280.000000000000000	2.000000000000000	635618638765928750	635618638765928750	cancelled	LIMIT	f	\N	BTC	10.000030000000000
2	1166	0.100000000000000	276.000000000000000	635618634287022500	635618634287022500	cancelled	LIMIT	t	\N	BTC	0.100000000000000
6	1166	300.000000000000000	1.000000000000000	635618640591085000	635618640591085000	cancelled	LIMIT	t	\N	BTC	300.000000000000000
41	1	2.370936109158650	258.961932313569000	635618658197960000	635656728468543402	filled	LIMIT	t	\N	BTC	0.000000000000000
62	1	10.403337873939100	1.081614777400910	635656727945714105	635656727945714105	filled	LIMIT	t	\N	LTC	0.000000000000000
21	1	0.494610993421920	259.306495765240000	635618656906866250	635618659445616250	filled	LIMIT	f	\N	BTC	0.000000000000000
11	1	2.276284907607490	245.505994286521000	635618656608428750	635618656608428750	filled	LIMIT	t	\N	BTC	0.000000000000000
7	1	1.235893304103940	304.960710185226000	635618656494991250	635618657180616250	filled	LIMIT	t	\N	BTC	0.000000000000000
14	1	0.287940234545590	275.013621479791000	635618656697803750	635618656697803750	filled	LIMIT	f	\N	BTC	0.000000000000000
16	1	1.307525358306200	285.027036366811000	635618656762647500	635618656762647500	filled	LIMIT	f	\N	BTC	0.000000000000000
54	1	12.474257311538900	0.886314816823376	635656727893993835	635656727893993835	filled	LIMIT	t	\N	LTC	0.000000000000000
18	1	0.706824526752730	293.632226927081000	635618656817335000	635618656967178750	filled	MARKET	f	\N	BTC	0.000000000000000
36	1	1.465321091918890	251.964041236352000	635618657356710000	635656727942745266	filled	LIMIT	f	\N	BTC	0.000000000000000
24	1	1.988722271979190	281.172714863183000	635618656997960000	635618657030303750	filled	LIMIT	t	\N	BTC	0.000000000000000
35	1	1.124890056031240	263.517898764516000	635618657330772500	635656730197817408	filled	LIMIT	f	\N	BTC	0.000000000000000
63	1	2.288042677002500	203.298323791412000	635656727953839356	635656728136970359	filled	LIMIT	t	\N	BTC	0.000000000000000
59	1	0.311516892542837	201.389290713234000	635656727931506738	635656728204785038	filled	LIMIT	f	\N	BTC	0.000000000000000
65	1	1.677795939230270	203.539623121082000	635656727967433516	635656728136970359	filled	LIMIT	t	\N	BTC	0.000000000000000
27	1	2.482299823305710	299.997696488908000	635618657089366250	635618657148116250	filled	LIMIT	f	\N	BTC	0.000000000000000
32	1	0.274420583282793	299.388064042427000	635618657237335000	635618657266710000	filled	LIMIT	f	\N	BTC	0.000000000000000
55	1	2.055322801254380	202.547038546118000	635656727903837907	635656727903837907	filled	LIMIT	t	\N	BTC	0.000000000000000
30	1	0.942922702963895	311.801587256324000	635618657180616250	635656728255268659	filled	LIMIT	f	\N	BTC	0.000000000000000
43	1	1.139544293349400	206.767838841708000	635656727824928983	635656727851651719	filled	LIMIT	f	\N	BTC	0.000000000000000
13	1	1.760016198623930	272.766917003722000	635618656666710000	635656728164002328	filled	LIMIT	f	\N	BTC	0.000000000000000
23	1	0.931640130435880	258.611594634080000	635618656967178750	635656727942745266	filled	LIMIT	t	\N	BTC	0.000000000000000
47	1	1.175909794948950	202.691747007081000	635656727851651719	635656727851651719	filled	LIMIT	t	\N	BTC	0.000000000000000
39	1	2.080825494872790	310.855038828894000	635618657452647500	635656727903837907	filled	LIMIT	f	\N	BTC	0.000000000000000

\.


--
-- Data for Name: stat; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY stat (id, indicator, "timestamp", value) FROM stdin;
\.


--
-- Name: stat_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('stat_id_seq', 1, false);


--
-- Data for Name: system; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY system (id, name, value) FROM stdin;
\.


--
-- Name: system_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('system_id_seq', 1, false);


--
-- Data for Name: ticket; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY ticket (id, title, "createdBy", "createdAt", status, department, "updatedAt", "updatedBy", "messageCount", "userId", importance) FROM stdin;
54	test	1172	1426439499	waitForSupport	security	1426494671	1172	4	\N	normal
\.


--
-- Name: ticket_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('ticket_id_seq', 158, true);


--
-- Data for Name: ticket_message; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY ticket_message (id, "createdAt", "createdBy", "ticketId", text, files) FROM stdin;
90	1426439499	1172	54	test
91	1426439524	1172	54	test	\N
\.


--
-- Name: ticket_message_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('ticket_message_id_seq', 99, true);


--
-- Data for Name: transaction; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY transaction (id, account_from, amount, "createdAt", hash, currency, account_to, user_from, user_to, side) FROM stdin;
139	1278	1.000000000000000	1426265936	\N	EUR	1286	1172	1172	f
140	1278	1.100000000000000	1426265947	\N	EUR	1286	1172	1172	f
141	1278	1.100000000000000	1426266109	\N	EUR	1286	1172	1172	f
142	1206	350.000000000000000	1426266588	\N	EUR	1214	1166	1166	f
143	1207	3.000000000000000	1426266593	\N	BTC	1215	1166	1166	f
144	1207	260.000000000000000	1426267199	\N	BTC	1215	1166	1166	f
145	1206	300.000000000000000	1426267226	\N	EUR	1214	1166	1166	f
146	1218	500.000000000000000	1426272008	\N	EUR	1226	1167	1167	f
147	1242	10000.000000000000000	1426272021	\N	EUR	1250	1169	1169	f
148	1278	100.000000000000000	1426493293	\N	EUR	1286	1172	1172	f
149	1286	100.000000000000000	1426493305	\N	EUR	1278	1172	1172	t
150	1286	1.000000000000000	1426493383	\N	EUR	1278	1172	1172	t
151	1278	100.000000000000000	1426537234	\N	EUR	1286	1172	1172	f
152	1278	5000.000000000000000	1426766853	\N	EUR	1286	1172	1172	f
153	1338	10.000000000000000	1426960710	\N	EUR	1346	1180	1180	f
154	1338	490.000000000000000	1426960752	\N	EUR	1346	1180	1180	f
155	1346	50.000000000000000	1426960787	\N	EUR	1338	1180	1180	t
156	1206	50000.000000000000000	1427219131	\N	EUR	1214	1166	1166	f
157	1278	20000.000000000000000	1427283533	\N	EUR	1286	1172	1172	f
158	1286	16000.000000000000000	1427283588	\N	EUR	1278	1172	1172	t
159	1278	30000.000000000000000	1427283749	\N	EUR	1286	1172	1172	f
160	1278	60897.800000000000000	1427384173	\N	EUR	1286	1172	1172	f
161	1279	1100.000000000000000	1427384180	\N	BTC	1287	1172	1172	f
\.


--
-- Data for Name: transaction_external; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY transaction_external (id, "accountId", amount, "createdAt", hash, currency, "gatewayId", type, "verifyStatus", "verifiedBy", details) FROM stdin;
52	1206	345.000000000000000	1426518861	\N	EUR	7	f	pending	1172	KUI
53	1314	1.000000000000000	1426850945	\N	EUR	7	f	pending	1172	\N
54	1279	100.000000000000000	1426854847	\N	BTC	5	f	pending	\N	\N
55	1279	100.000000000000000	1426854860	\N	BTC	5	f	pending	\N	\N
56	1279	100.000000000000000	1426855014	\N	BTC	5	f	pending	\N	\N
57	1279	100.000000000000000	1426855043	\N	BTC	5	f	pending	\N	\N
58	1279	100.000000000000000	1426855162	\N	BTC	5	f	pending	\N	\N
59	1279	100.000000000000000	1426855213	\N	BTC	5	f	pending	\N	\N
60	1279	100.000000000000000	1426855262	\N	BTC	5	f	pending	\N	\N
61	1279	100.000000000000000	1426856407	\N	BTC	5	f	pending	\N	\N
63	1401	0.000100000000000	1427289359	\N	BTC	2	f	done	\N	{"txid":"b3064f0f7d0e600ca13081516a18b6c36f1080aa3d5beb7300cad457fb51de8d","address":"191mo9MFigHqqA3RSUQ7HkLUXnFEQF3N5X"}
62	1401	0.000100000000000	1427287061	\N	BTC	2	f	done	\N	{"txid":"8cdb77da2eec94cf29fb646799f5267461d62083924308852b0a767356d403c3","address":"191mo9MFigHqqA3RSUQ7HkLUXnFEQF3N5X"}
64	1412	1123.000000000000000	1427382969	\N	EUR	7	f	pending	1172	\N
65	1400	111.000000000000000	1427383029	\N	EUR	7	f	pending	1172	\N
66	1206	150.000000000000000	1427383032	\N	EUR	7	f	rejected	1172	\N
67	1400	3.000000000000000	1427383081	\N	EUR	7	f	rejected	1172	\N
68	1580	5000.000000000000000	1427814664	\N	EUR	7	f	rejected	1172	\N
69	1580	5000.000000000000000	1427814708	\N	EUR	4	f	pending	\N	\N
70	1581	30.000000000000000	1427814734	\N	BTC	5	f	pending	\N	\N
\.


--
-- Name: transaction_external_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('transaction_external_id_seq', 70, true);


--
-- Name: transaction_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('transaction_id_seq', 161, true);


--
-- Data for Name: user; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY "user" (id, password, email, "lastLoginAt", "emailVerification", "inviteCode", "createdAt", blocked, type, "verifiedBy", "verifiedData", "verifiedStatus", "verifiedReason", "twoFA", "verifiedAt", "referalPay", "parentId", "referalCode") FROM stdin;
1173	e0d7ba4486aa7b0f9bcf4c4018975d58c8e69210a802461bc997007a7c4a40424f3c6d7497f6bf5c292b59b0d0d107da2877984c288f54cb7afebcaa9a1ce465	b@b.com	\N	\N	\N	2015-03-13 12:55:37.740576-04	f	super	\N	\N	withoutVerify	\N	f	\N	\N	\N	\N
1178	b3e52ddac0b385bd3d0591b0298f8d6eaef8cf5f24d0905560b2e88d85e0e6f92913a157a2faabed92584ca8d87d93bfed1caddd55b5648b59faa59bc101228b	hiall@spacebtc.tk	1426525877	\N	\N	2015-03-16 13:10:54.708251-04	f	trader	\N	\N	withoutVerify	\N	f	\N	\N	\N	\N
1172	515102e839f7779f978194042df76509cd492e6d1981b79a9df3be178c2b78c64ece9b1233df9871e213bda88f7628dfbbfe5ffc315dfa5f2d0bf5208f14ba18	a@a.com	1428683388	\N	\N	2015-03-13 12:55:31.730172-04	f	super	\N	\N	withoutVerify	\N	f	\N	\N	\N	\N
1200	9f7f8a1eb432dbc378ecc928ed0a0713d30d602b39540f0de1dd1e4b0f47ce2b47754272aa12dcae64c7d2e04b893436e4242d497f644b231061d7b4cd9007b1	autologinspbtctest3@yopmail.com	1427468638	\N	\N	2015-03-27 11:03:13.967207-04	f	trader	\N	\N	withoutVerify	\N	f	\N	\N	\N	\N

\.


--
-- Data for Name: user_early; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY user_early (id, email, ip) FROM stdin;
12	test@test.com	127.0.0.1
\.


--
-- Name: user_early_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('user_early_seq', 35, true);


--
-- Data for Name: user_external_address; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY user_external_address (id, "userId", currency, address, "accountId") FROM stdin;
\.


--
-- Name: user_external_address_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('user_external_address_id_seq', 1, false);


--
-- Name: user_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('user_id_seq', 1221, true);


--
-- Data for Name: user_invite; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY user_invite (id, activated, email) FROM stdin;
feebfab9e88f5418bcbecfe623e3c75826c784ebef04b4676e0ef7e2dcb75bd3775de18b92bddeacd330329d67a93d54aea02eabf66cabeaa56e0f67a5b82385	f	test@test.com
88525649fc5ef20f10869ddc42950f4e360fca26732b4ed92c1a8f4f88aa27aeaccb931a5699ba05941e1dbe23276cc1ad4e8223624331ea02b944d67fcf59f9	f	test@test.com
346cf732da2605c5f0e409ffc6d9b06d06802c076ef6e2dd0b696302e09c9ca171798afd26ccf9d8f683b717aedd622af41e268ac59abf87600c20ab5fa7df8d	f	test@test.com
c2d10af95beea62bbb605b590f903dd3c5326efa017a31f5f0489185998be367f702c690a61ae1ef8c46f96924fd5c14f2c80a7df325ee3f071f3354ac6dad6d	f	test@test.com
f9abcce05a7f05c791a10d8ac45aa06b519936c11b689bed4a7407f565aba8bce3a9d15da6378999db40f0d7d0d54552a34e0c5a77090dcb405ae36f4f028c32	f	test@test.com
dc2303c3f1f94eef12f4d2b59eb80f0d1db163a2836dbc56e6c3dacf012cc2dbc36bfd77cfbfc650752914e4a3a3284c8812c11bb7f0f52ee3a74f996ad1dab7	f	test@test.com
\.


--
-- Data for Name: user_log; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY user_log (id, "userId", "createdAt", action, data, ip) FROM stdin;
1884	1104	1425470234	login	User has logged in	127.0.0.1
1885	1106	1425472101	login	User has logged in	127.0.0.1
1886	1105	1425472104	login	User has logged in	127.0.0.1
1887	1109	1425473184	login	User has logged in	127.0.0.1
\.


--
-- Name: user_log_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('user_log_id_seq', 2930, true);


--
-- Data for Name: user_phone; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY user_phone (id, phone) FROM stdin;
\.


--
-- Data for Name: user_transaction_confurm; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY user_transaction_confurm (id, code, "userId", details, used) FROM stdin;
2	d8d0264ac5807afc5f8b4988167894b9dc68a296401bdf4badd3513263051fc7405700ecbc42a1324886db6b6a20ba29b2987447f027244378f4985266eb78c0	1195	{"gatewayId":2,"accountId":1387,"amount":0,"currency":"BTC","payment":[{"name":"BTC In","fields":[{"title":"Address","name":"address","type":"Text","value":"1PL2Emy69SKqq88ThBt2Akhf1aaa3LqdpP"},{"title":"QR ","name":"Qr","type":"Qr","value":"1PL2Emy69SKqq88ThBt2Akhf1aaa3LqdpP"}]}],"type":0}	f
3	d8d0264ac5807afc5f8b4988167894b9dc68a296401bdf4badd3513263051fc7405700ecbc42a1324886db6b6a20ba29b2987447f027244378f4985266eb78c0	1195	{"gatewayId":2,"accountId":1387,"amount":0,"currency":"BTC","payment":[{"name":"BTC In","fields":[{"title":"Address","name":"address","type":"Text","value":"1PL2Emy69SKqq88ThBt2Akhf1aaa3LqdpP"},{"title":"QR ","name":"Qr","type":"Qr","value":"1PL2Emy69SKqq88ThBt2Akhf1aaa3LqdpP"}]}],"type":0}	f
4	d8d0264ac5807afc5f8b4988167894b9dc68a296401bdf4badd3513263051fc7405700ecbc42a1324886db6b6a20ba29b2987447f027244378f4985266eb78c0	1195	{"gatewayId":2,"accountId":1387,"amount":0,"currency":"BTC","payment":[{"name":"BTC In","fields":[{"title":"Address","name":"address","type":"Text","value":"1PL2Emy69SKqq88ThBt2Akhf1aaa3LqdpP"},{"title":"QR ","name":"Qr","type":"Qr","value":"1PL2Emy69SKqq88ThBt2Akhf1aaa3LqdpP"}]}],"type":0}	f
5	d8d0264ac5807afc5f8b4988167894b9dc68a296401bdf4badd3513263051fc7405700ecbc42a1324886db6b6a20ba29b2987447f027244378f4985266eb78c0	1195	{"gatewayId":2,"accountId":1387,"amount":0,"currency":"BTC","payment":[{"name":"BTC In","fields":[{"title":"Address","name":"address","type":"Text","value":"1PL2Emy69SKqq88ThBt2Akhf1aaa3LqdpP"},{"title":"QR ","name":"Qr","type":"Qr","value":"1PL2Emy69SKqq88ThBt2Akhf1aaa3LqdpP"}]}],"type":0}	f
6	d8d0264ac5807afc5f8b4988167894b9dc68a296401bdf4badd3513263051fc7405700ecbc42a1324886db6b6a20ba29b2987447f027244378f4985266eb78c0	1195	{"gatewayId":2,"accountId":1387,"amount":0,"currency":"BTC","payment":[{"name":"BTC In","fields":[{"title":"Address","name":"address","type":"Text","value":"1PL2Emy69SKqq88ThBt2Akhf1aaa3LqdpP"},{"title":"QR ","name":"Qr","type":"Qr","value":"1PL2Emy69SKqq88ThBt2Akhf1aaa3LqdpP"}]}],"type":0}	f
7	d8d0264ac5807afc5f8b4988167894b9dc68a296401bdf4badd3513263051fc7405700ecbc42a1324886db6b6a20ba29b2987447f027244378f4985266eb78c0	1195	{"gatewayId":2,"accountId":1387,"amount":0,"currency":"BTC","payment":[{"name":"BTC In","fields":[{"title":"Address","name":"address","type":"Text","value":"1PL2Emy69SKqq88ThBt2Akhf1aaa3LqdpP"},{"title":"QR ","name":"Qr","type":"Qr","value":"1PL2Emy69SKqq88ThBt2Akhf1aaa3LqdpP"}]}],"type":0}	f
8	057edf5a008f8991582d6c8cb6637d7fa138e3ebc6f6fe740151bd2928ed3b7ae920911a174bb95071b0d1c05ee696ae275fc31f098a4b298f931e9d92d5d850	1195	{"gatewayId":2,"accountId":1387,"amount":"34444","currency":"BTC","payment":[{"name":"BTC Out","fields":[{"title":"Address","name":"address","type":"String","required":true,"value":"435345345345"}]}],"type":1}	f
9	38d1df666cc13f604a7960708a58ece60f889a216a9d77982678d65714d92e3c498caaebe93597e3c883a824ce317814b1f6bc9fd46fad160a4b7441a6d2dfff	1195	{"gatewayId":6,"accountId":1386,"amount":0,"currency":"EUR","payment":[{"name":"Euro Out","fields":[{"title":"Beneficiary Name","name":"beneficiary","type":"String","required":true,"value":""},{"title":"Beneficiary IBAN","name":"iban","type":"String","required":true,"value":""},{"title":"Beneficiary SWIFT","name":"swift","type":"String","required":true,"value":""},{"title":"Beneficiary Address","name":"address","type":"String","required":true,"value":""},{"title":"Name of bank","name":"bank_name","type":"String","required":true,"value":""},{"title":"Bank Address","name":"bank_address","type":"String","required":true,"value":""}]}],"type":1}	f
10	d8d0264ac5807afc5f8b4988167894b9dc68a296401bdf4badd3513263051fc7405700ecbc42a1324886db6b6a20ba29b2987447f027244378f4985266eb78c0	1195	{"gatewayId":2,"accountId":1387,"amount":0,"currency":"BTC","payment":[{"name":"BTC In","fields":[{"title":"Address","name":"address","type":"Text","value":"1PL2Emy69SKqq88ThBt2Akhf1aaa3LqdpP"},{"title":"QR ","name":"Qr","type":"Qr","value":"1PL2Emy69SKqq88ThBt2Akhf1aaa3LqdpP"}]}],"type":0}	f
11	d8d0264ac5807afc5f8b4988167894b9dc68a296401bdf4badd3513263051fc7405700ecbc42a1324886db6b6a20ba29b2987447f027244378f4985266eb78c0	1195	{"gatewayId":2,"accountId":1387,"amount":0,"currency":"BTC","payment":[{"name":"BTC In","fields":[{"title":"Address","name":"address","type":"Text","value":"1PL2Emy69SKqq88ThBt2Akhf1aaa3LqdpP"},{"title":"QR ","name":"Qr","type":"Qr","value":"1PL2Emy69SKqq88ThBt2Akhf1aaa3LqdpP"}]}],"type":0}	f
12	d8d0264ac5807afc5f8b4988167894b9dc68a296401bdf4badd3513263051fc7405700ecbc42a1324886db6b6a20ba29b2987447f027244378f4985266eb78c0	1195	{"gatewayId":2,"accountId":1387,"amount":0,"currency":"BTC","payment":[{"name":"BTC In","fields":[{"title":"Address","name":"address","type":"Text","value":"1PL2Emy69SKqq88ThBt2Akhf1aaa3LqdpP"},{"title":"QR ","name":"Qr","type":"Qr","value":"1PL2Emy69SKqq88ThBt2Akhf1aaa3LqdpP"}]}],"type":0}	f
13	01325cc78e69b15bff29ba93537991e2bdfc55bad478e6bd29a7509bae6570d01ffc1f7f08d11e6e63c1f695cb3d517d0deb4da5a5f9153c77e3d3cc56a00e12	1166	{"gatewayId":2,"accountId":1207,"amount":"200","currency":"BTC","payment":[{"name":"BTC Out","fields":[{"title":"Address","name":"address","type":"String","required":true,"value":"1PL2Emy69SKqq88ThBt2Akhf1aaa3LqdpP"}]}],"type":1}	f
\.


--
-- Name: user_transaction_confurm_id; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('user_transaction_confurm_id', 13, true);


--
-- Name: account_guid_key; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY account
    ADD CONSTRAINT account_guid_key UNIQUE (guid);


--
-- Name: account_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY account
    ADD CONSTRAINT account_pkey PRIMARY KEY (id);


--
-- Name: account_publicId_key; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY account
    ADD CONSTRAINT "account_publicId_key" UNIQUE ("publicId");


--
-- Name: alarm_code_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY alarm_code
    ADD CONSTRAINT alarm_code_pkey PRIMARY KEY (id);


--
-- Name: candles_15m_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY candles_15m
    ADD CONSTRAINT candles_15m_pkey PRIMARY KEY (id);


--
-- Name: candles_1h_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY candles_1h
    ADD CONSTRAINT candles_1h_pkey PRIMARY KEY (id);


--
-- Name: candles_1m_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY candles_1m
    ADD CONSTRAINT candles_1m_pkey PRIMARY KEY (id);


--
-- Name: candles_5m_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY candles_5m
    ADD CONSTRAINT candles_5m_pkey PRIMARY KEY (id);


--
-- Name: coin_address_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY coin_address
    ADD CONSTRAINT coin_address_pkey PRIMARY KEY (id);


--
-- Name: deal_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY deal
    ADD CONSTRAINT deal_pkey PRIMARY KEY (id);


--
-- Name: file_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY file
    ADD CONSTRAINT file_pkey PRIMARY KEY (id);


--
-- Name: file_uid_key; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY file
    ADD CONSTRAINT file_uid_key UNIQUE (uid);


--
-- Name: gateway_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY gateway
    ADD CONSTRAINT gateway_pkey PRIMARY KEY (id);


--
-- Name: news_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY news
    ADD CONSTRAINT news_pkey PRIMARY KEY (id);


--
-- Name: order_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY "order"
    ADD CONSTRAINT order_pkey PRIMARY KEY (id);


--
-- Name: stat_indicator_timestamp_key; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY stat
    ADD CONSTRAINT stat_indicator_timestamp_key UNIQUE (indicator, "timestamp");


--
-- Name: stat_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY stat
    ADD CONSTRAINT stat_pkey PRIMARY KEY (id);


--
-- Name: system_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY system
    ADD CONSTRAINT system_pkey PRIMARY KEY (id);


--
-- Name: ticket_message_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY ticket_message
    ADD CONSTRAINT ticket_message_pkey PRIMARY KEY (id);


--
-- Name: ticket_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY ticket
    ADD CONSTRAINT ticket_pkey PRIMARY KEY (id);


--
-- Name: transaction_external_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY transaction_external
    ADD CONSTRAINT transaction_external_pkey PRIMARY KEY (id);


--
-- Name: transaction_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY transaction
    ADD CONSTRAINT transaction_pkey PRIMARY KEY (id);


--
-- Name: user_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY "user"
    ADD CONSTRAINT user_email_key UNIQUE (email);


--
-- Name: user_external_address_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY user_external_address
    ADD CONSTRAINT user_external_address_pkey PRIMARY KEY (id);


--
-- Name: user_invite_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY user_invite
    ADD CONSTRAINT user_invite_pkey PRIMARY KEY (id);


--
-- Name: user_log_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY user_log
    ADD CONSTRAINT user_log_pkey PRIMARY KEY (id);


--
-- Name: user_phone_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY user_phone
    ADD CONSTRAINT user_phone_pkey PRIMARY KEY (id);


--
-- Name: user_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY "user"
    ADD CONSTRAINT user_pkey PRIMARY KEY (id);


--
-- Name: user_transaction_confurm_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY user_transaction_confurm
    ADD CONSTRAINT user_transaction_confurm_pkey PRIMARY KEY (id);


--
-- PostgreSQL database dump complete
--

