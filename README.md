# docker-athletica
[Athletica](https://www.swiss-athletics.ch/de/wettkaempfe/wettkampfsupport/athletica) for docker.

Clone project:

```bash
git clone https://github.com/ppoile/docker-athletica
```

Run service:

```bash
docker-compose up
```

Stop service:

```bash
docker-compose down
```

Restart with fresh containers:

```bash
docker-compose down
docker volume rm dockerathletica_db_data
docker-compose up
```
