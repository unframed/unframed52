import os, getpass, string, subprocess

def file_exists(filename):
    return os.path.exists(filename)

def shell_exec(command):
    return subprocess.check_output(command, shell=True)

def nginx_start ():
    options = {
        'root': os.path.abspath('test'),
        'user': getpass.getuser()
        }
    nginx = 'test/nginx.conf'
    nginx_conf = 'test/out/nginx.conf'
    template = string.Template(open(nginx).read())
    open(nginx_conf, 'w').write(template.substitute(options))
    return shell_exec('sudo /usr/sbin/nginx -c '+os.path.abspath(nginx_conf))

def fpm_start ():
    options = {
        'root': os.path.abspath('test'),
        'user': getpass.getuser()
        }
    fpm = 'test/php-fpm.conf'
    fpm_conf = 'test/out/php-fpm.conf'
    template = string.Template(open(fpm).read())
    open(fpm_conf, 'w').write(template.substitute(options))
    return subprocess.Popen(['/usr/sbin/php5-fpm', '-y', fpm_conf]).pid

if __name__ == '__main__':
    if not file_exists('test/pid'):
        nginx_start()
    if not file_exists('test/php-pid'):
        fpm_start()